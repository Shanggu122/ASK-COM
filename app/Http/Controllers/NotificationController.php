<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\CalendarOverride;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function getNotifications()
    {
        $userId = Auth::user()->Stud_ID ?? null;

        if (!$userId) {
            return response()->json(["notifications" => []]);
        }

        // Ensure global suspension notifications exist for this student (idempotent)
        try {
            $today = Carbon::today("Asia/Manila")->toDateString();
            // Only include genuine suspension-type overrides here.
            // Exclude professor leave (scope_type=professor with reason_key=prof_leave)
            $upcoming = CalendarOverride::query()
                ->where("effect", "block_all")
                ->where(function ($q) {
                    $q->whereNull("scope_type")->orWhere("scope_type", "!=", "professor");
                })
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "prof_leave");
                })
                // Exclude End Year from student autogeneration
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "end_year");
                })
                ->where("end_date", ">=", $today)
                ->orderBy("start_date", "asc")
                ->limit(10)
                ->get();
            foreach ($upcoming as $ov) {
                $startHuman = Carbon::parse($ov->start_date)->format("M d, Y");
                $endHuman = Carbon::parse($ov->end_date)->format("M d, Y");
                $rangeText =
                    $ov->start_date === $ov->end_date
                        ? "on {$startHuman}"
                        : "from {$startHuman} to {$endHuman}";
                $rk = $ov->reason_key;
                $reasonTxt = trim((string) ($ov->reason_text ?? ""));
                if ($reasonTxt === "" && $rk) {
                    $map = [
                        "weather" => "Inclement weather",
                        "prof_leave" => "Professor leave",
                        "health" => "Health advisory",
                        "emergency" => "Emergency advisory",
                    ];
                    $reasonTxt = $map[$rk] ?? ucfirst(str_replace("_", " ", (string) $rk));
                }
                if ($reasonTxt === "") {
                    $reasonTxt = "administrative reasons";
                }
                $title = "Suspension of Class";
                $message = "No classes {$rangeText} due to {$reasonTxt}.";
                // Defensive: skip any that read as End Year
                if (preg_match("/end\s*year/i", $reasonTxt)) {
                    continue;
                }
                // Idempotent check by (user_id, type, message)
                $exists = Notification::where("user_id", $userId)
                    ->where("type", "suspention_day")
                    ->where("message", $message)
                    ->exists();
                if (!$exists) {
                    Notification::createSystem($userId, $title, $message, "suspention_day");
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Fetch all notifications for this student, then reduce to latest per booking/group
        $all = Notification::where("user_id", $userId)
            // Students should not see professor-leave system notices
            ->whereNotIn("type", ["professor_leave", "professor_leave_removed"])
            ->orderBy("updated_at", "desc")
            ->orderBy("created_at", "desc")
            ->get();

        // Group by booking id, treating system (null booking_id) as their own unique item via id
        $latestPerGroup = $all
            ->groupBy(function ($n) {
                return $n->booking_id ? "b_" . $n->booking_id : "solo_" . $n->id;
            })
            ->map(function ($items) {
                return $items->sortByDesc("updated_at")->first();
            });

        $ordered = $latestPerGroup
            ->sortByDesc(function ($n) {
                return $n->updated_at ?? $n->created_at;
            })
            ->values()
            ->take(10);

        // Append synthesized suspension notices (in case DB creation lags)
        try {
            $today = Carbon::today("Asia/Manila")->toDateString();
            $upcoming = CalendarOverride::query()
                ->where("effect", "block_all")
                ->where(function ($q) {
                    $q->whereNull("scope_type")->orWhere("scope_type", "!=", "professor");
                })
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "prof_leave");
                })
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "end_year");
                })
                ->where("end_date", ">=", $today)
                ->orderBy("start_date", "asc")
                ->limit(3)
                ->get();
            foreach ($upcoming as $ov) {
                $startHuman = Carbon::parse($ov->start_date)->format("M d, Y");
                $endHuman = Carbon::parse($ov->end_date)->format("M d, Y");
                $rangeText =
                    $ov->start_date === $ov->end_date
                        ? "on {$startHuman}"
                        : "from {$startHuman} to {$endHuman}";
                $rk = $ov->reason_key;
                $reasonTxt = trim((string) ($ov->reason_text ?? ""));
                if ($reasonTxt === "" && $rk) {
                    $map = [
                        "weather" => "Inclement weather",
                        "prof_leave" => "Professor leave",
                        "health" => "Health advisory",
                        "emergency" => "Emergency advisory",
                    ];
                    $reasonTxt = $map[$rk] ?? ucfirst(str_replace("_", " ", (string) $rk));
                }
                if ($reasonTxt === "") {
                    $reasonTxt = "administrative reasons";
                }
                $msg = "No classes {$rangeText} due to {$reasonTxt}.";
                if (preg_match("/end\s*year/i", $reasonTxt)) {
                    continue;
                }
                $existsInList = $ordered->contains(function ($n) use ($msg) {
                    return ($n->type ?? "") === "suspention_day" && ($n->message ?? "") === $msg;
                });
                if (!$existsInList) {
                    $ordered->prepend(
                        (object) [
                            "id" => null,
                            "user_id" => $userId,
                            "booking_id" => null,
                            "type" => "suspention_day",
                            "title" => "Suspension of Class",
                            "message" => $msg,
                            "is_read" => false,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ],
                    );
                }
            }
        } catch (\Throwable $e) {
        }

        return response()->json(["notifications" => $ordered->take(10)->values()]);
    }

    public function markAsRead(Request $request)
    {
        $notificationId = $request->get("notification_id");

        $notification = Notification::find($notificationId);

        if ($notification && $notification->user_id == (Auth::user()->Stud_ID ?? null)) {
            $notification->markAsRead();
            return response()->json(["success" => true]);
        }

        return response()->json(["success" => false]);
    }

    public function markAllAsRead()
    {
        $userId = Auth::user()->Stud_ID ?? null;

        if (!$userId) {
            return response()->json(["success" => false]);
        }

        Notification::where("user_id", $userId)
            ->where("is_read", false)
            ->update(["is_read" => true]);

        return response()->json(["success" => true]);
    }

    public function getUnreadCount()
    {
        $userId = Auth::user()->Stud_ID ?? null;

        if (!$userId) {
            return response()->json(["count" => 0]);
        }

        $count = Notification::where("user_id", $userId)->where("is_read", false)->count();

        return response()->json(["count" => $count]);
    }

    // Professor notification methods
    public function getProfessorNotifications()
    {
        $professorId = Auth::guard("professor")->user()->Prof_ID ?? null;

        if (!$professorId) {
            return response()->json(["notifications" => []]);
        }
        // Ensure global suspension notifications exist for this professor (idempotent)
        try {
            $today = Carbon::today("Asia/Manila")->toDateString();
            $upcoming = CalendarOverride::query()
                ->where("effect", "block_all")
                // Exclude professor leave entirely from professor autogen notices
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "prof_leave");
                })
                ->where(function ($q) {
                    $q->whereNull("scope_type")->orWhere("scope_type", "!=", "professor");
                })
                // Exclude End Year from professor autogeneration
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "end_year");
                })
                ->where("end_date", ">=", $today)
                ->orderBy("start_date", "asc")
                ->limit(10)
                ->get();
            foreach ($upcoming as $ov) {
                $startHuman = Carbon::parse($ov->start_date)->format("M d, Y");
                $endHuman = Carbon::parse($ov->end_date)->format("M d, Y");
                $rangeText =
                    $ov->start_date === $ov->end_date
                        ? "on {$startHuman}"
                        : "from {$startHuman} to {$endHuman}";
                $rk = $ov->reason_key;
                $reasonTxt = trim((string) ($ov->reason_text ?? ""));
                if ($reasonTxt === "" && $rk) {
                    $map = [
                        "weather" => "Inclement weather",
                        "prof_leave" => "Professor leave",
                        "health" => "Health advisory",
                        "emergency" => "Emergency advisory",
                    ];
                    $reasonTxt = $map[$rk] ?? ucfirst(str_replace("_", " ", (string) $rk));
                }
                if ($reasonTxt === "") {
                    $reasonTxt = "administrative reasons";
                }
                $title = "Suspension of Class";
                $message = "No classes {$rangeText} due to {$reasonTxt}.";
                if (preg_match("/end\s*year/i", $reasonTxt)) {
                    continue;
                }
                // Idempotent check by (user_id, type, message)
                $exists = Notification::where("user_id", $professorId)
                    ->where("type", "suspention_day")
                    ->where("message", $message)
                    ->exists();
                if (!$exists) {
                    Notification::createSystem($professorId, $title, $message, "suspention_day");
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        // Fetch all relevant notifications first (limit to recent range for performance if needed)
        $all = Notification::where("user_id", $professorId)
            // Exclude system notifications not meant for professors
            ->whereNotIn("type", ["professor_leave", "professor_leave_removed"])
            ->orderBy("updated_at", "desc")
            ->orderBy("created_at", "desc")
            ->get();

        // Group by booking (null booking_id treated as unique via fallback key) then select the latest (by updated_at) per group
        $latestPerBooking = $all
            ->groupBy(function ($n) {
                return $n->booking_id ? "b_" . $n->booking_id : "solo_" . $n->id;
            })
            ->map(function ($items) {
                return $items->sortByDesc("updated_at")->first();
            });

        // Final ordering: strictly newest first using updated_at (fallback created_at)
        $ordered = $latestPerBooking
            ->sortByDesc(function ($n) {
                return $n->updated_at ?? $n->created_at;
            })
            ->values()
            ->take(10);

        // Append synthesized suspension notices (in case DB creation lags)
        try {
            $today = Carbon::today("Asia/Manila")->toDateString();
            $upcoming = CalendarOverride::query()
                ->where("effect", "block_all")
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "prof_leave");
                })
                ->where(function ($q) {
                    $q->whereNull("scope_type")->orWhere("scope_type", "!=", "professor");
                })
                ->where(function ($q) {
                    $q->whereNull("reason_key")->orWhere("reason_key", "!=", "end_year");
                })
                ->where("end_date", ">=", $today)
                ->orderBy("start_date", "asc")
                ->limit(3)
                ->get();
            foreach ($upcoming as $ov) {
                $startHuman = Carbon::parse($ov->start_date)->format("M d, Y");
                $endHuman = Carbon::parse($ov->end_date)->format("M d, Y");
                $rangeText =
                    $ov->start_date === $ov->end_date
                        ? "on {$startHuman}"
                        : "from {$startHuman} to {$endHuman}";
                $rk = $ov->reason_key;
                $reasonTxt = trim((string) ($ov->reason_text ?? ""));
                if ($reasonTxt === "" && $rk) {
                    $map = [
                        "weather" => "Inclement weather",
                        "prof_leave" => "Professor leave",
                        "health" => "Health advisory",
                        "emergency" => "Emergency advisory",
                    ];
                    $reasonTxt = $map[$rk] ?? ucfirst(str_replace("_", " ", (string) $rk));
                }
                if ($reasonTxt === "") {
                    $reasonTxt = "administrative reasons";
                }
                $msg = "No classes {$rangeText} due to {$reasonTxt}.";
                if (preg_match("/end\s*year/i", $reasonTxt)) {
                    continue;
                }
                $existsInList = $ordered->contains(function ($n) use ($msg) {
                    return ($n->type ?? "") === "suspention_day" && ($n->message ?? "") === $msg;
                });
                if (!$existsInList) {
                    $ordered->prepend(
                        (object) [
                            "id" => null,
                            "user_id" => $professorId,
                            "booking_id" => null,
                            "type" => "suspention_day",
                            "title" => "Suspension of Class",
                            "message" => $msg,
                            "is_read" => false,
                            "created_at" => now(),
                            "updated_at" => now(),
                        ],
                    );
                }
            }
        } catch (\Throwable $e) {
        }

        return response()->json(["notifications" => $ordered->take(10)->values()]);
    }

    public function markProfessorAsRead(Request $request)
    {
        $notificationId = $request->get("notification_id");

        $notification = Notification::find($notificationId);

        if (
            $notification &&
            $notification->user_id == (Auth::guard("professor")->user()->Prof_ID ?? null)
        ) {
            $notification->markAsRead();
            return response()->json(["success" => true]);
        }

        return response()->json(["success" => false]);
    }

    public function markAllProfessorAsRead()
    {
        $professorId = Auth::guard("professor")->user()->Prof_ID ?? null;

        if (!$professorId) {
            return response()->json(["success" => false]);
        }

        Notification::where("user_id", $professorId)
            ->where("is_read", false)
            ->update(["is_read" => true]);

        return response()->json(["success" => true]);
    }

    public function getProfessorUnreadCount()
    {
        $professorId = Auth::guard("professor")->user()->Prof_ID ?? null;

        if (!$professorId) {
            return response()->json(["unread_count" => 0]);
        }

        $unreadCount = Notification::where("user_id", $professorId)
            ->where("is_read", false)
            ->count();

        return response()->json(["unread_count" => $unreadCount]);
    }

    // Admin notification methods - for admin to see ALL notifications system-wide
    public function getAdminNotifications()
    {
        // Get all notifications with booking and user details
        $allNotifications = DB::table("notifications as n")
            ->leftJoin("t_consultation_bookings as b", "b.Booking_ID", "=", "n.booking_id")
            ->leftJoin("professors as bp", "bp.Prof_ID", "=", "b.Prof_ID")
            ->leftJoin("t_student as bs", "bs.Stud_ID", "=", "b.Stud_ID")
            ->leftJoin("t_student as s", "s.Stud_ID", "=", "n.user_id")
            ->leftJoin("professors as p", "p.Prof_ID", "=", "n.user_id")
            ->select([
                "n.id",
                "n.user_id",
                "n.booking_id",
                "n.type",
                "n.title",
                "n.message",
                "n.is_read",
                "n.created_at",
                "n.updated_at",
                DB::raw("COALESCE(s.Name, p.Name) as user_name"),
                "bp.Name as professor_name",
                "bs.Name as student_name",
                DB::raw(
                    "CASE WHEN s.Stud_ID IS NOT NULL THEN 1 ELSE 0 END as is_student_notification",
                ),
            ])
            ->orderBy("n.updated_at", "desc")
            ->get();

        // Filter to show only one notification per booking (prefer student notifications)
        $uniqueNotifications = collect();
        $seenBookings = [];

        foreach ($allNotifications as $notification) {
            // Some system notifications (like professor leave) may not be tied to a booking
            $bookingKey = $notification->booking_id
                ? (string) $notification->booking_id
                : "sys_" . $notification->type . "_" . $notification->user_id;

            if (!isset($seenBookings[$bookingKey])) {
                // First encountered (currently most recent due to initial ordering)
                $seenBookings[$bookingKey] = $notification;
                $uniqueNotifications->push($notification);
                continue;
            }

            $existing = $seenBookings[$bookingKey];

            // Decide replacement: pick the one with newer updated_at.
            // If same timestamp, prefer student notification for clearer admin context.
            if (
                $notification->updated_at > $existing->updated_at ||
                ($notification->updated_at == $existing->updated_at &&
                    $notification->is_student_notification &&
                    !$existing->is_student_notification)
            ) {
                // Replace existing entry in collection
                $uniqueNotifications = $uniqueNotifications->filter(function ($item) use (
                    $existing,
                ) {
                    return $item->id !== $existing->id;
                });
                $uniqueNotifications->push($notification);
                $seenBookings[$bookingKey] = $notification;
            }
        }

        // Sort by updated_at desc and limit
        $notifications = $uniqueNotifications->sortByDesc("updated_at")->take(50)->values();

        // Transform messages for admin view
        $notifications = $notifications->map(function ($notification) {
            // Create admin-appropriate messages
            $adminMessage = $notification->message;

            if ($notification->student_name && $notification->professor_name) {
                // Create admin-specific messages based on notification type
                switch ($notification->type) {
                    case "accepted":
                        $adminMessage = "{$notification->student_name}'s consultation with {$notification->professor_name} has been accepted.";
                        break;
                    case "completed":
                        $adminMessage = "{$notification->student_name}'s consultation with {$notification->professor_name} has been completed.";
                        break;
                    case "completion_pending":
                        $adminMessage = "{$notification->professor_name} requested completion confirmation from {$notification->student_name}.";
                        break;
                    case "completion_declined":
                        $adminMessage = "{$notification->student_name} declined the completion request from {$notification->professor_name}.";
                        break;
                    case "rescheduled":
                        $adminMessage = "{$notification->student_name}'s consultation with {$notification->professor_name} has been rescheduled.";
                        break;
                    case "cancelled":
                        $adminMessage = "{$notification->student_name}'s consultation with {$notification->professor_name} has been cancelled.";
                        break;
                    case "booking_request":
                        $adminMessage = "{$notification->student_name} has booked a consultation with {$notification->professor_name}.";
                        break;
                    default:
                        // For any other types, try to replace "Your" with student name if it exists
                        if (strpos($adminMessage, "Your ") === 0 && $notification->student_name) {
                            $adminMessage = str_replace(
                                "Your ",
                                "{$notification->student_name}'s ",
                                $adminMessage,
                            );
                        }
                        // Remove admin-inappropriate phrases
                        $adminMessage = str_replace(
                            [
                                "Please rate your experience.",
                                "Please rate your experience",
                                "Rate your experience.",
                                "Rate your experience",
                            ],
                            "",
                            $adminMessage,
                        );
                        $adminMessage = trim($adminMessage);
                        break;
                }
            }

            // Update the message for admin view
            $notification->message = $adminMessage;

            return $notification;
        });

        return response()->json(["notifications" => $notifications]);
    }

    public function markAdminAsRead(Request $request)
    {
        $notificationId = $request->get("notification_id");

        $notification = Notification::find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(["success" => true]);
        }

        return response()->json(["success" => false]);
    }

    public function markAllAdminAsRead()
    {
        // Admin can mark ALL notifications as read
        Notification::where("is_read", false)->update(["is_read" => true]);

        return response()->json(["success" => true]);
    }

    public function getAdminUnreadCount()
    {
        // Count ALL unread notifications for admin overview
        $unreadCount = Notification::where("is_read", false)->count();

        return response()->json(["unread_count" => $unreadCount]);
    }
}
