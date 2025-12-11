<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CalendarOverride;
use App\Models\Notification;
use App\Services\CalendarOverrideService;
use Carbon\Carbon;

class AdminCalendarOverrideController extends Controller
{
    // Public read-only: returns global (scope_type='all') overrides in date range
    public function publicList(Request $request)
    {
        $data = $request->validate([
            "start_date" => "required|date",
            "end_date" => "nullable|date",
        ]);
        $start = Carbon::parse($data["start_date"])->toDateString();
        $end = isset($data["end_date"]) ? Carbon::parse($data["end_date"])->toDateString() : $start;

        $rows = CalendarOverride::query()
            ->where("scope_type", "all")
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween("start_date", [$start, $end])
                    ->orWhereBetween("end_date", [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where("start_date", "<=", $start)->where("end_date", ">=", $end);
                    });
            })
            ->orderBy("start_date", "asc")
            ->get();

        $period = \Carbon\CarbonPeriod::create($start, $end);
        $byDate = [];
        foreach ($period as $d) {
            $byDate[$d->format("Y-m-d")] = [];
        }
        foreach ($rows as $ov) {
            $subPeriod = \Carbon\CarbonPeriod::create($ov->start_date, $ov->end_date);
            foreach ($subPeriod as $d) {
                $key = $d->format("Y-m-d");
                if (!array_key_exists($key, $byDate)) {
                    continue;
                }
                $label = null;
                if ($ov->effect === "holiday") {
                    $label = $ov->reason_text ?: "Holiday";
                } elseif ($ov->effect === "block_all") {
                    $label = $ov->reason_key === "prof_leave" ? "Leave" : "Suspension";
                } elseif ($ov->effect === "force_mode") {
                    $label = "Force " . ucfirst($ov->allowed_mode ?? "mode");
                }
                $byDate[$key][] = [
                    "effect" => $ov->effect,
                    "allowed_mode" => $ov->allowed_mode,
                    "reason_key" => $ov->reason_key,
                    "reason_text" => $ov->reason_text,
                    "label" => $label,
                ];
            }
        }
        return response()->json(["success" => true, "overrides" => $byDate]);
    }

    // Professor read: returns global overrides plus professor-scoped overrides for the logged-in professor
    public function professorList(Request $request)
    {
        $data = $request->validate([
            "start_date" => "required|date",
            "end_date" => "nullable|date",
        ]);
        $start = Carbon::parse($data["start_date"])->toDateString();
        $end = isset($data["end_date"]) ? Carbon::parse($data["end_date"])->toDateString() : $start;

        $prof = auth()->guard("professor")->user();
        if (!$prof) {
            return response()->json(["success" => false, "error" => "Unauthorized"], 401);
        }
        $profId = (int) ($prof->Prof_ID ?? 0);

        $rows = CalendarOverride::query()
            ->where(function ($q) use ($profId) {
                $q->where("scope_type", "all")->orWhere(function ($qq) use ($profId) {
                    $qq->where("scope_type", "professor")->where("scope_id", $profId);
                });
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween("start_date", [$start, $end])
                    ->orWhereBetween("end_date", [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where("start_date", "<=", $start)->where("end_date", ">=", $end);
                    });
            })
            ->orderBy("start_date", "asc")
            ->get();

        $period = \Carbon\CarbonPeriod::create($start, $end);
        $byDate = [];
        foreach ($period as $d) {
            $byDate[$d->format("Y-m-d")] = [];
        }
        foreach ($rows as $ov) {
            $subPeriod = \Carbon\CarbonPeriod::create($ov->start_date, $ov->end_date);
            foreach ($subPeriod as $d) {
                $key = $d->format("Y-m-d");
                if (!array_key_exists($key, $byDate)) {
                    continue;
                }
                $label = null;
                if ($ov->effect === "holiday") {
                    $label = $ov->reason_text ?: "Holiday";
                } elseif ($ov->effect === "block_all") {
                    $label = $ov->reason_key === "prof_leave" ? "Leave" : "Suspension";
                } elseif ($ov->effect === "force_mode") {
                    $label = "Force " . ucfirst($ov->allowed_mode ?? "mode");
                }
                $byDate[$key][] = [
                    "effect" => $ov->effect,
                    "allowed_mode" => $ov->allowed_mode,
                    "reason_key" => $ov->reason_key,
                    "reason_text" => $ov->reason_text,
                    "label" => $label,
                ];
            }
        }
        return response()->json(["success" => true, "overrides" => $byDate]);
    }

    // Public read-only: returns global overrides plus professor-scoped overrides for a given professor ID
    // This enables student booking calendars to reflect professor-specific overrides.
    public function publicProfessorList(Request $request)
    {
        $data = $request->validate([
            "prof_id" => "required|integer",
            "start_date" => "required|date",
            "end_date" => "nullable|date",
        ]);
        $start = Carbon::parse($data["start_date"])->toDateString();
        $end = isset($data["end_date"]) ? Carbon::parse($data["end_date"])->toDateString() : $start;
        $profId = (int) $data["prof_id"];

        $rows = CalendarOverride::query()
            ->where(function ($q) use ($profId) {
                $q->where("scope_type", "all")->orWhere(function ($qq) use ($profId) {
                    $qq->where("scope_type", "professor")->where("scope_id", $profId);
                });
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween("start_date", [$start, $end])
                    ->orWhereBetween("end_date", [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where("start_date", "<=", $start)->where("end_date", ">=", $end);
                    });
            })
            ->orderBy("start_date", "asc")
            ->get();

        $period = \Carbon\CarbonPeriod::create($start, $end);
        $byDate = [];
        foreach ($period as $d) {
            $byDate[$d->format("Y-m-d")] = [];
        }
        foreach ($rows as $ov) {
            $sub = \Carbon\CarbonPeriod::create($ov->start_date, $ov->end_date);
            foreach ($sub as $d) {
                $key = $d->format("Y-m-d");
                if (!array_key_exists($key, $byDate)) {
                    continue;
                }
                $label = null;
                if ($ov->effect === "holiday") {
                    $label = $ov->reason_text ?: "Holiday";
                } elseif ($ov->effect === "block_all") {
                    $label = $ov->reason_key === "prof_leave" ? "Leave" : "Suspension";
                } elseif ($ov->effect === "force_mode") {
                    $label = "Force " . ucfirst($ov->allowed_mode ?? "mode");
                }
                $byDate[$key][] = [
                    "effect" => $ov->effect,
                    "allowed_mode" => $ov->allowed_mode,
                    "reason_key" => $ov->reason_key,
                    "reason_text" => $ov->reason_text,
                    "label" => $label,
                ];
            }
        }
        return response()->json(["success" => true, "overrides" => $byDate]);
    }
    public function list(Request $request)
    {
        $data = $request->validate([
            "start_date" => "required|date",
            "end_date" => "nullable|date",
        ]);
        $start = Carbon::parse($data["start_date"])->toDateString();
        $end = isset($data["end_date"]) ? Carbon::parse($data["end_date"])->toDateString() : $start;

        // Fetch overrides overlapping the requested range
        $rows = CalendarOverride::query()
            // Admin calendar should not reflect professor leave days
            ->where(function ($q) {
                $q->whereNull("reason_key")->orWhere("reason_key", "!=", "prof_leave");
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween("start_date", [$start, $end])
                    ->orWhereBetween("end_date", [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->where("start_date", "<=", $start)->where("end_date", ">=", $end);
                    });
            })
            ->orderBy("start_date", "asc")
            ->get();

        $period = \Carbon\CarbonPeriod::create($start, $end);
        $byDate = [];
        foreach ($period as $d) {
            $byDate[$d->format("Y-m-d")] = [];
        }
        foreach ($rows as $ov) {
            $subPeriod = \Carbon\CarbonPeriod::create($ov->start_date, $ov->end_date);
            foreach ($subPeriod as $d) {
                $key = $d->format("Y-m-d");
                if (!array_key_exists($key, $byDate)) {
                    continue;
                } // skip dates outside requested window
                $label = null;
                if ($ov->effect === "holiday") {
                    $label = $ov->reason_text ?: "Holiday";
                } elseif ($ov->effect === "block_all") {
                    $label = "Suspension";
                } elseif ($ov->effect === "force_mode") {
                    $label = "Force " . ucfirst($ov->allowed_mode ?? "mode");
                }
                $byDate[$key][] = [
                    "effect" => $ov->effect,
                    "allowed_mode" => $ov->allowed_mode,
                    "reason_key" => $ov->reason_key,
                    "reason_text" => $ov->reason_text,
                    "label" => $label,
                ];
            }
        }
        return response()->json([
            "success" => true,
            "overrides" => $byDate,
        ]);
    }
    public function preview(Request $request)
    {
        $data = $request->validate([
            "start_date" => "required|date",
            "end_date" => "nullable|date",
            "scope_type" => "nullable|in:all,professor",
            "scope_id" => "nullable|integer",
            "effect" => "required|in:force_mode,block_all,holiday",
            "allowed_mode" => "nullable|in:online,onsite",
            "auto_reschedule" => "nullable|boolean",
        ]);
        $start = Carbon::parse($data["start_date"])->startOfDay();
        $end = isset($data["end_date"])
            ? Carbon::parse($data["end_date"])->endOfDay()
            : $start->copy()->endOfDay();

        // Build date strings matching Booking_Date format
        $dateStrings = [];
        foreach (\Carbon\CarbonPeriod::create($start, $end) as $d) {
            $dateStrings[] = $d->format("D M d Y");
        }

        $q = DB::table("t_consultation_bookings")->whereIn("Booking_Date", $dateStrings);
        if (($data["scope_type"] ?? "all") === "professor" && !empty($data["scope_id"])) {
            $q->where("Prof_ID", (int) $data["scope_id"]);
        }
        $affected = $q->get([
            "Booking_ID",
            "Prof_ID",
            "Stud_ID",
            "Consult_type_ID",
            "Custom_Type",
            "Booking_Date",
            "Mode",
            "Status",
        ]);

        $response = [
            "success" => true,
            "affected_count" => $affected->count(),
            "effect" => $data["effect"],
            "dates" => $dateStrings,
        ];

        // If Forced Online with auto-reschedule, report how many would be rescheduled (exam/quiz only)
        if (
            $data["effect"] === "force_mode" &&
            ($data["allowed_mode"] ?? null) === "online" &&
            ($data["auto_reschedule"] ?? false)
        ) {
            $isExamQuiz = function ($row) {
                if (!empty($row->Custom_Type)) {
                    $ct = strtolower($row->Custom_Type);
                    return str_contains($ct, "exam") || str_contains($ct, "quiz");
                }
                $typeName = DB::table("t_consultation_types")
                    ->where("Consult_type_ID", $row->Consult_type_ID)
                    ->value("Consult_Type");
                return strcasecmp($typeName, "Special Quiz or Exam") === 0;
            };
            $response["reschedule_candidate_count"] = $affected->filter($isExamQuiz)->count();
        }

        return response()->json($response);
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            "start_date" => "required|date",
            "end_date" => "nullable|date",
            "scope_type" => "nullable|in:all,professor",
            "scope_id" => "nullable|integer",
        ]);

        $start = Carbon::parse($data["start_date"])->toDateString();
        $end = isset($data["end_date"]) ? Carbon::parse($data["end_date"])->toDateString() : $start;

        $q = CalendarOverride::query()->where(function ($q) use ($start, $end) {
            $q->whereBetween("start_date", [$start, $end])
                ->orWhereBetween("end_date", [$start, $end])
                ->orWhere(function ($qq) use ($start, $end) {
                    $qq->where("start_date", "<=", $start)->where("end_date", ">=", $end);
                });
        });

        if (($data["scope_type"] ?? "all") !== "all") {
            $q->where("scope_type", $data["scope_type"] ?? "all")->where(
                "scope_id",
                $data["scope_id"] ?? null,
            );
        }

        $deleted = $q->delete();

        return response()->json([
            "success" => true,
            "deleted" => $deleted,
        ]);
    }
    public function apply(Request $request)
    {
        $data = $request->validate([
            "start_date" => "required|date",
            "end_date" => "nullable|date",
            "scope_type" => "nullable|in:all,professor",
            "scope_id" => "nullable|integer",
            "effect" => "required|in:force_mode,block_all,holiday",
            "allowed_mode" => "nullable|in:online,onsite",
            "reason_key" => "nullable|string",
            "reason_text" => "nullable|string",
            "auto_reschedule" => "nullable|boolean",
        ]);

        $startDate = Carbon::parse($data["start_date"])->toDateString();
        $endDate = isset($data["end_date"])
            ? Carbon::parse($data["end_date"])->toDateString()
            : $startDate;

        // Safely resolve created_by from admin guard (may be null in some contexts)
        $admin = auth()->guard("admin")->user();
        $createdBy = $admin ? $admin->Admin_ID ?? null : null;

        $override = CalendarOverride::create([
            "start_date" => $startDate,
            "end_date" => $endDate,
            "scope_type" => $data["scope_type"] ?? "all",
            "scope_id" => $data["scope_id"] ?? null,
            "effect" => $data["effect"],
            "allowed_mode" => $data["allowed_mode"] ?? null,
            "reason_key" => $data["reason_key"] ?? null,
            "reason_text" => $data["reason_text"] ?? null,
            "created_by" => $createdBy,
        ]);

        // Process affected bookings
        $dateStrings = [];
        foreach (\Carbon\CarbonPeriod::create($startDate, $endDate) as $d) {
            $dateStrings[] = Carbon::parse($d)->format("D M d Y");
        }
        $q = DB::table("t_consultation_bookings")->whereIn("Booking_Date", $dateStrings);
        if (($data["scope_type"] ?? "all") === "professor" && !empty($data["scope_id"])) {
            $q->where("Prof_ID", (int) $data["scope_id"]);
        }
        $bookings = $q->get();

        $updated = 0;
        $rescheduled = 0;
        $cancelled = 0;
        $unchanged = 0;

        // Helper to identify Exam/Quiz
        $isExamQuiz = function ($row) {
            if (!empty($row->Custom_Type)) {
                return str_contains(strtolower($row->Custom_Type), "exam") ||
                    str_contains(strtolower($row->Custom_Type), "quiz");
            }
            $typeName = DB::table("t_consultation_types")
                ->where("Consult_type_ID", $row->Consult_type_ID)
                ->value("Consult_Type");
            return strcasecmp($typeName, "Special Quiz or Exam") === 0;
        };

        // Sort bookings so Exam/Quiz first
        $sorted = $bookings->sortByDesc(function ($b) use ($isExamQuiz) {
            return $isExamQuiz($b) ? 1 : 0;
        });

        foreach ($sorted as $b) {
            if ($data["effect"] === "force_mode") {
                $allowed = $data["allowed_mode"] ?? null;
                $autoResched = (bool) ($data["auto_reschedule"] ?? false);

                // If Forced Online with auto-reschedule enabled, reschedule Exam/Quiz only; others switch mode to online
                if ($allowed === "online" && $autoResched && $isExamQuiz($b)) {
                    $newDate = $this->findNextDate($b, 60);
                    if ($newDate) {
                        // Derive a student-facing reason from provided reason_text or reason_key
                        $reasonTxt = trim((string) ($data["reason_text"] ?? ""));
                        if ($reasonTxt === "" && !empty($data["reason_key"])) {
                            $rk = (string) $data["reason_key"];
                            // Map common keys to human labels; fallback to formatted key
                            $map = [
                                "weather" => "Inclement weather",
                                "power_outage" => "Power outage",
                                "health_advisory" => "Health advisory",
                                "holiday_shift" => "Holiday shift",
                                "facility" => "Facility issue",
                                "prof_leave" => "Professor leave",
                                "health" => "Health advisory",
                                "emergency" => "Emergency advisory",
                            ];
                            $reasonTxt = $map[$rk] ?? ucfirst(str_replace("_", " ", $rk));
                        }
                        if ($reasonTxt === "") {
                            $reasonTxt = "administrative reasons";
                        }

                        DB::table("t_consultation_bookings")
                            ->where("Booking_ID", $b->Booking_ID)
                            ->update([
                                "Status" => "rescheduled",
                                "Booking_Date" => $newDate,
                                "reschedule_reason" => $reasonTxt,
                            ]);
                        $rescheduled++;
                        try {
                            \App\Models\Notification::updateNotificationStatus(
                                $b->Booking_ID,
                                "rescheduled",
                                DB::table("professors")
                                    ->where("Prof_ID", $b->Prof_ID)
                                    ->value("Name"),
                                $newDate,
                                $data["reason_text"] ?? null,
                            );
                        } catch (\Throwable $e) {
                        }
                        try {
                            event(
                                new \App\Events\BookingUpdated((int) $b->Prof_ID, [
                                    "event" => "BookingUpdated",
                                    "Booking_ID" => (int) $b->Booking_ID,
                                    "student_id" => (int) $b->Stud_ID,
                                    "Status" => "rescheduled",
                                    "Booking_Date" => $newDate,
                                ]),
                            );
                            if ($b->Stud_ID) {
                                event(
                                    new \App\Events\BookingUpdatedStudent((int) $b->Stud_ID, [
                                        "event" => "BookingUpdated",
                                        "Booking_ID" => (int) $b->Booking_ID,
                                        "Status" => "rescheduled",
                                        "Booking_Date" => $newDate,
                                    ]),
                                );
                            }
                        } catch (\Throwable $e) {
                        }
                        continue; // next booking
                    } else {
                        // no date found; fall back to unchanged (or could cancel, but spec says only reschedule candidates)
                        $unchanged++;
                        continue;
                    }
                }

                // For non-exam/quiz (or when auto-resched disabled), enforce mode change
                if (!empty($allowed) && $b->Mode !== $allowed) {
                    DB::table("t_consultation_bookings")
                        ->where("Booking_ID", $b->Booking_ID)
                        ->update(["Mode" => $allowed]);
                    $updated++;
                    try {
                        \App\Models\Notification::updateNotificationStatus(
                            $b->Booking_ID,
                            "rescheduled",
                            DB::table("professors")->where("Prof_ID", $b->Prof_ID)->value("Name"),
                            $b->Booking_Date,
                            "Mode changed by admin",
                        );
                    } catch (\Throwable $e) {
                    }
                    try {
                        event(
                            new \App\Events\BookingUpdated((int) $b->Prof_ID, [
                                "event" => "BookingUpdated",
                                "Booking_ID" => (int) $b->Booking_ID,
                                "student_id" => (int) $b->Stud_ID,
                                "Status" => $b->Status,
                                "Booking_Date" => $b->Booking_Date,
                                "Mode" => $allowed,
                            ]),
                        );
                        if ($b->Stud_ID) {
                            event(
                                new \App\Events\BookingUpdatedStudent((int) $b->Stud_ID, [
                                    "event" => "BookingUpdated",
                                    "Booking_ID" => (int) $b->Booking_ID,
                                    "Status" => $b->Status,
                                    "Booking_Date" => $b->Booking_Date,
                                    "Mode" => $allowed,
                                ]),
                            );
                        }
                    } catch (\Throwable $e) {
                    }
                } else {
                    $unchanged++;
                }
            } elseif ($data["effect"] === "block_all") {
                if (!($data["auto_reschedule"] ?? false)) {
                    // cancel only
                    DB::table("t_consultation_bookings")
                        ->where("Booking_ID", $b->Booking_ID)
                        ->update(["Status" => "cancelled"]);
                    $cancelled++;
                    try {
                        \App\Models\Notification::updateNotificationStatus(
                            $b->Booking_ID,
                            "cancelled",
                            DB::table("professors")->where("Prof_ID", $b->Prof_ID)->value("Name"),
                            $b->Booking_Date,
                            $data["reason_text"] ?? null,
                        );
                    } catch (\Throwable $e) {
                    }
                    continue;
                }
                // auto-reschedule
                $newDate = $this->findNextDate($b, 60); // search up to 60 days
                if ($newDate) {
                    // Derive a student-facing reason from provided reason_text or reason_key
                    $reasonTxt = trim((string) ($data["reason_text"] ?? ""));
                    if ($reasonTxt === "" && !empty($data["reason_key"])) {
                        $rk = (string) $data["reason_key"];
                        $map = [
                            "weather" => "Inclement weather",
                            "power_outage" => "Power outage",
                            "health_advisory" => "Health advisory",
                            "holiday_shift" => "Holiday shift",
                            "facility" => "Facility issue",
                            "prof_leave" => "Professor leave",
                            "health" => "Health advisory",
                            "emergency" => "Emergency advisory",
                        ];
                        $reasonTxt = $map[$rk] ?? ucfirst(str_replace("_", " ", $rk));
                    }
                    if ($reasonTxt === "") {
                        $reasonTxt = "administrative reasons";
                    }

                    DB::table("t_consultation_bookings")
                        ->where("Booking_ID", $b->Booking_ID)
                        ->update([
                            "Status" => "rescheduled",
                            "Booking_Date" => $newDate,
                            "reschedule_reason" => $reasonTxt,
                        ]);
                    $rescheduled++;
                    try {
                        \App\Models\Notification::updateNotificationStatus(
                            $b->Booking_ID,
                            "rescheduled",
                            DB::table("professors")->where("Prof_ID", $b->Prof_ID)->value("Name"),
                            $newDate,
                            $data["reason_text"] ?? null,
                        );
                    } catch (\Throwable $e) {
                    }
                    try {
                        event(
                            new \App\Events\BookingUpdated((int) $b->Prof_ID, [
                                "event" => "BookingUpdated",
                                "Booking_ID" => (int) $b->Booking_ID,
                                "student_id" => (int) $b->Stud_ID,
                                "Status" => "rescheduled",
                                "Booking_Date" => $newDate,
                            ]),
                        );
                        if ($b->Stud_ID) {
                            event(
                                new \App\Events\BookingUpdatedStudent((int) $b->Stud_ID, [
                                    "event" => "BookingUpdated",
                                    "Booking_ID" => (int) $b->Booking_ID,
                                    "Status" => "rescheduled",
                                    "Booking_Date" => $newDate,
                                ]),
                            );
                        }
                    } catch (\Throwable $e) {
                    }
                } else {
                    DB::table("t_consultation_bookings")
                        ->where("Booking_ID", $b->Booking_ID)
                        ->update(["Status" => "cancelled"]);
                    $cancelled++;
                    try {
                        \App\Models\Notification::updateNotificationStatus(
                            $b->Booking_ID,
                            "cancelled",
                            DB::table("professors")->where("Prof_ID", $b->Prof_ID)->value("Name"),
                            $b->Booking_Date,
                            "No available slot within search window",
                        );
                    } catch (\Throwable $e) {
                    }
                }
            } else {
                // holiday: no forced changes to bookings by default
                $unchanged++;
            }
        }

        // After processing, if this is a Suspension (block_all), notify professors and affected students
        // IMPORTANT: Do NOT notify professors/students for End Year ranges
        try {
            if (($data["effect"] ?? null) === "block_all") {
                $title = "Suspension of Class";

                $startHuman = Carbon::parse($startDate)->format("M d, Y");
                $endHuman = Carbon::parse($endDate)->format("M d, Y");
                $rangeText =
                    $startDate === $endDate
                        ? "on {$startHuman}"
                        : "from {$startHuman} to {$endHuman}";

                $reasonTxt = trim((string) ($data["reason_text"] ?? ""));
                if ($reasonTxt === "" && !empty($data["reason_key"])) {
                    $rk = $data["reason_key"];
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

                $reschedText =
                    $data["auto_reschedule"] ?? false
                        ? "Affected bookings will be rescheduled."
                        : "Affected bookings have been cancelled.";

                $message = "No classes {$rangeText} due to {$reasonTxt}. {$reschedText}";

                // Skip sending notifications if this block_all represents End Year
                $rkLower = strtolower((string) ($data["reason_key"] ?? ""));
                $rtLower = strtolower((string) ($data["reason_text"] ?? ""));
                $isEndYear = $rkLower === "end_year" || strpos($rtLower, "end year") !== false;

                if (!$isEndYear) {
                    // Notify ALL professors regardless of scope
                    $profRecipients = DB::table("professors")->pluck("Prof_ID")->toArray();
                    foreach ($profRecipients as $pid) {
                        Notification::createSystem((int) $pid, $title, $message, "suspention_day");
                    }

                    // Notify ALL students, even without bookings on the affected dates
                    $studentRecipients = DB::table("t_student")->pluck("Stud_ID")->toArray();
                    foreach ($studentRecipients as $sid) {
                        Notification::createSystem((int) $sid, $title, $message, "suspention_day");
                    }
                }
            }
        } catch (\Throwable $e) {
            // Do not fail the apply call because of notification errors
        }

        return response()->json([
            "success" => true,
            "override_id" => $override->id,
            "updated_mode" => $updated,
            "rescheduled" => $rescheduled,
            "cancelled" => $cancelled,
            "unchanged" => $unchanged,
        ]);
    }

    private function findNextDate($bookingRow, int $maxDays = 30): ?string
    {
        $profId = (int) $bookingRow->Prof_ID;
        $mode = $bookingRow->Mode;
        $tz = "Asia/Manila";
        $start = Carbon::now($tz)->startOfDay();
        // Try the day after original booking first
        try {
            $start = Carbon::createFromFormat("D M d Y", $bookingRow->Booking_Date, $tz)
                ->addDay()
                ->startOfDay();
        } catch (\Exception $e) {
        }

        $capacityStatuses = ["approved", "rescheduled"];
        $overrideSvc = app(CalendarOverrideService::class);

        // Exam/Quiz must be onsite-only
        $isExamQuiz = false;
        if (!empty($bookingRow->Custom_Type)) {
            $isExamQuiz =
                str_contains(strtolower($bookingRow->Custom_Type), "exam") ||
                str_contains(strtolower($bookingRow->Custom_Type), "quiz");
        } else {
            $typeName = DB::table("t_consultation_types")
                ->where("Consult_type_ID", $bookingRow->Consult_type_ID)
                ->value("Consult_Type");
            $isExamQuiz = strcasecmp($typeName, "Special Quiz or Exam") === 0;
        }
        if ($isExamQuiz) {
            $mode = "onsite";
        }

        // Determine allowed weekdays based on professor schedule; fallback to Mon-Fri if none
        $allowedDays = $this->getProfessorScheduleDays($profId);
        if (empty($allowedDays)) {
            $allowedDays = [1, 2, 3, 4, 5]; // Mon-Fri default
        }

        for ($i = 0; $i < $maxDays; $i++) {
            $d = $start->copy()->addDays($i);
            // Filter by professor's declared schedule (day of week)
            if (!in_array($d->dayOfWeek, $allowedDays, true)) {
                continue;
            }
            $dateStr = $d->format("D M d Y");
            $ov = $overrideSvc->evaluate($profId, $dateStr);
            if ($ov["blocked"] ?? false) {
                continue;
            }
            if (!empty($ov["forced_mode"]) && $ov["forced_mode"] !== $mode) {
                continue;
            }

            // Capacity check
            $existingApproved = DB::table("t_consultation_bookings")
                ->where("Prof_ID", $profId)
                ->where("Booking_Date", $dateStr)
                ->whereIn("Status", $capacityStatuses)
                ->count();
            if ($existingApproved >= 5) {
                continue;
            }

            // Mode lock: if first approved/rescheduled exists, must match booking mode
            $firstExisting = DB::table("t_consultation_bookings")
                ->where("Prof_ID", $profId)
                ->where("Booking_Date", $dateStr)
                ->whereIn("Status", $capacityStatuses)
                ->orderBy("Booking_ID", "asc")
                ->first();
            if ($firstExisting && $firstExisting->Mode && $mode !== $firstExisting->Mode) {
                continue;
            }

            return $dateStr;
        }
        return null;
    }

    /**
     * Parse professor weekly schedule into allowed day-of-week integers (Carbon: 0=Sun..6=Sat).
     * Returns empty array if nothing could be parsed.
     */
    private function getProfessorScheduleDays(int $profId): array
    {
        try {
            $sched = DB::table("professors")->where("Prof_ID", $profId)->value("Schedule");
            if (!$sched) {
                return [];
            }
            // Normalize delimiters and split lines
            $norm = str_replace(["&#10;", "<br>", "<br/>", "<br />"], "\n", $sched);
            $lines = preg_split('/\r?\n+/', $norm);
            $map = [
                "sunday" => 0,
                "sun" => 0,
                "monday" => 1,
                "mon" => 1,
                "tuesday" => 2,
                "tue" => 2,
                "tues" => 2,
                "wednesday" => 3,
                "wed" => 3,
                "thursday" => 4,
                "thu" => 4,
                "thur" => 4,
                "thurs" => 4,
                "friday" => 5,
                "fri" => 5,
                "saturday" => 6,
                "sat" => 6,
            ];
            $out = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === "") {
                    continue;
                }
                // Expect formats like "Monday: 9:00-10:00" but be forgiving
                $prefix = strtolower(trim(strtok($line, ":")));
                if (array_key_exists($prefix, $map)) {
                    $out[] = $map[$prefix];
                } else {
                    // Try to match day names anywhere in the line
                    foreach ($map as $name => $dow) {
                        if (str_contains($prefix, $name)) {
                            $out[] = $dow;
                            break;
                        }
                    }
                }
            }
            return array_values(array_unique($out));
        } catch (\Throwable $e) {
            return [];
        }
    }
}
