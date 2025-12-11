<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use App\Events\MessageSent;
use App\Events\PresencePing;
use App\Models\ChatMessage;
use App\Support\ProfilePhotoPath;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    // Send message (used by both student and professor routes)
    public function sendMessage(Request $request)
    {
        try {
            // Validate attachments: allow only office docs/PDF up to 25MB per file
            $validator = Validator::make($request->all(), [
                "files.*" => "nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:25600",
                "file" => "nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:25600",
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        "status" => "Invalid attachment",
                        "error" =>
                            "Only PDF, Word, Excel, or PowerPoint files up to 25 MB per file are allowed.",
                        "details" => $validator->errors()->all(),
                    ],
                    422,
                );
            }
            $bookingId = $request->input("bookingId");
            $sender = $request->input("sender");
            $recipient = $request->input("recipient"); // may be null for public/system later
            $messageText = trim((string) $request->input("message", ""));
            $status = "Delivered";
            $clientUuid = $request->input("client_uuid"); // used for optimistic UI dedupe (not stored)
            if (!$clientUuid) {
                $clientUuid = null;
            }
            $createdAt = now("Asia/Manila");

            // Participant resolution: either provided or inferred from guards
            $studId = $request->input("stud_id");
            $profId = $request->input("prof_id");

            if (!$studId && Auth::check()) {
                $studId = Auth::user()->Stud_ID ?? null;
            }
            if (!$profId && Auth::guard("professor")->check()) {
                $profId = Auth::guard("professor")->user()->Prof_ID ?? null;
            }

            // If still missing one participant, try infer from sender/recipient when they match naming
            // (light heuristic; can be expanded)

            if (!$studId && is_numeric($sender) && strpos($sender, "@") === false) {
                $studId = $sender;
            }
            if (!$profId && is_numeric($recipient) && strpos($recipient, "@") === false) {
                $profId = $recipient;
            }

            if (!$studId || !$profId) {
                return response()->json(
                    [
                        "status" => "Error",
                        "error" => "stud_id and prof_id are required for direct messaging.",
                    ],
                    422,
                );
            }

            // If bookingId missing or null, set to 0 (sentinel) for legacy compatibility
            if (!$bookingId) {
                $bookingId = 0;
            }

            $broadcastBatch = [];

            // Handle multiple files (each as its own message, text sent separately once)
            if ($request->hasFile("files")) {
                foreach ($request->file("files") as $file) {
                    $fileMsg = new ChatMessage();
                    $fileMsg->Booking_ID = $bookingId;
                    $fileMsg->Stud_ID = $studId;
                    $fileMsg->Prof_ID = $profId;
                    $fileMsg->Sender = $sender;
                    $fileMsg->Recipient = $recipient;
                    $fileMsg->status = $status;
                    $fileMsg->Created_At = $createdAt;
                    $fileMsg->file_path = $file->store("chat_files", "public");
                    $fileMsg->file_type = $file->getMimeType();
                    $fileMsg->original_name = $file->getClientOriginalName();
                    $fileMsg->Message = ""; // keep empty so text isn't duplicated per file
                    $fileMsg->save();
                    $broadcastBatch[] = [
                        "message" => "",
                        "stud_id" => $studId,
                        "prof_id" => $profId,
                        "sender" => $sender,
                        "file" => $fileMsg->file_path,
                        "file_type" => $fileMsg->file_type,
                        "original_name" => $fileMsg->original_name,
                        "created_at_iso" => $createdAt->toIso8601String(),
                        "client_uuid" => $clientUuid,
                    ];
                }
            }

            // Single file (legacy param 'file')
            if ($request->hasFile("file")) {
                $file = $request->file("file");
                $fileMsg = new ChatMessage();
                $fileMsg->Booking_ID = $bookingId;
                $fileMsg->Stud_ID = $studId;
                $fileMsg->Prof_ID = $profId;
                $fileMsg->Sender = $sender;
                $fileMsg->Recipient = $recipient;
                $fileMsg->status = $status;
                $fileMsg->Created_At = $createdAt;
                $fileMsg->file_path = $file->store("chat_files", "public");
                $fileMsg->file_type = $file->getMimeType();
                $fileMsg->original_name = $file->getClientOriginalName();
                $fileMsg->Message = "";
                $fileMsg->save();
                $broadcastBatch[] = [
                    "message" => "",
                    "stud_id" => $studId,
                    "prof_id" => $profId,
                    "sender" => $sender,
                    "file" => $fileMsg->file_path,
                    "file_type" => $fileMsg->file_type,
                    "original_name" => $fileMsg->original_name,
                    "created_at_iso" => $createdAt->toIso8601String(),
                    "client_uuid" => $clientUuid,
                ];
            }

            // Create separate text message if provided
            if ($messageText !== "") {
                $textMsg = new ChatMessage();
                $textMsg->Booking_ID = $bookingId;
                $textMsg->Stud_ID = $studId;
                $textMsg->Prof_ID = $profId;
                $textMsg->Sender = $sender;
                $textMsg->Recipient = $recipient;
                $textMsg->status = $status;
                $textMsg->Created_At = $createdAt; // same timestamp batch
                $textMsg->Message = $messageText;
                $textMsg->save();
                $broadcastBatch[] = [
                    "message" => $messageText,
                    "stud_id" => $studId,
                    "prof_id" => $profId,
                    "sender" => $sender,
                    "file" => null,
                    "file_type" => null,
                    "original_name" => null,
                    "created_at_iso" => $createdAt->toIso8601String(),
                    "client_uuid" => $clientUuid,
                ];
            }

            if (!$request->hasFile("files") && !$request->hasFile("file") && $messageText === "") {
                return response()->json(["status" => "Nothing to send"]);
            }

            // Broadcast each saved message/file
            foreach ($broadcastBatch as $payload) {
                event(new \App\Events\MessageSent($payload));
            }

            return response()->json(["status" => "Message sent!"]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    "status" => "Error",
                    "error" => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                ],
                500,
            );
        }
    }

    // Professor entry points delegate to the same logic
    public function sendMessageprof(Request $request)
    {
        return $this->sendMessage($request);
    }

    public function sendFile(Request $request)
    {
        return $this->sendMessage($request);
    }

    public function showMessages()
    {
        $user = Auth::user();
        if (!$user || !isset($user->Stud_ID)) {
            // Redirect guests to landing page instead of /login
            return redirect()
                ->route("landing")
                ->with("error", "You must be logged in as a student to view messages.");
        }
        // Day-based eligibility (Asia/Manila): allow video call if there is an approved/rescheduled
        // booking for TODAY (ignore exact time window).
        $now = now("Asia/Manila");
        $todayPad = $now->format("D M d Y"); // e.g. Mon Oct 06 2025
        $todayNoPad = $now->format("D M j Y"); // e.g. Mon Oct 6 2025
        $todayIso = $now->toDateString(); // YYYY-mm-dd (in case column is DATE)
        $capacityStatuses = ["approved", "rescheduled"];

        $meetingLinkColumn = $this->detectColumn("t_consultation_bookings", [
            "Meeting_Link",
            "meeting_link",
        ]);
        $bookingTimeColumn = $this->detectColumn("t_consultation_bookings", [
            "Booking_Time",
            "booking_time",
        ]);
        $meetingLinkExpr = $meetingLinkColumn ? "NULLIF(b.`{$meetingLinkColumn}`, '')" : "NULL";
        $bookingTimeExpr = $bookingTimeColumn ? "NULLIF(b.`{$bookingTimeColumn}`, '')" : "NULL";

        $eligibleToday = DB::table("t_consultation_bookings as b")
            ->select(
                "b.Prof_ID",
                DB::raw("1 as can_video_call"),
                DB::raw("MAX({$meetingLinkExpr}) as meeting_link"),
                DB::raw("MIN({$bookingTimeExpr}) as booking_time"),
                DB::raw("MIN(NULLIF(b.Booking_Date, '')) as booking_date"),
                DB::raw("COUNT(DISTINCT b.Stud_ID) as student_count"),
            )
            ->where("b.Stud_ID", $user->Stud_ID)
            ->whereIn("b.Status", $capacityStatuses)
            ->whereIn("b.Booking_Date", [$todayPad, $todayNoPad, $todayIso])
            ->groupBy("b.Prof_ID");
        // Direct messaging mode: aggregate latest chat per professor from t_chat_messages using Stud_ID/Prof_ID
        $latest = DB::table("t_chat_messages as m")
            ->where("m.Stud_ID", $user->Stud_ID)
            ->select([
                "m.Prof_ID",
                DB::raw("MAX(m.Created_At) as last_message_time"),
                DB::raw(
                    'SUBSTRING_INDEX(GROUP_CONCAT(m.Message ORDER BY m.Created_At DESC), ",", 1) as last_message',
                ),
                DB::raw(
                    'SUBSTRING_INDEX(GROUP_CONCAT(m.Sender ORDER BY m.Created_At DESC), ",", 1) as last_sender',
                ),
            ])

            ->groupBy("m.Prof_ID");

        $professors = DB::table("professors as prof")
            ->leftJoinSub($latest, "lm", function ($join) {
                $join->on("lm.Prof_ID", "=", "prof.Prof_ID");
            })
            ->leftJoinSub($eligibleToday, "elig", function ($join) {
                $join->on("elig.Prof_ID", "=", "prof.Prof_ID");
            })
            ->select([
                "prof.Name as name",
                "prof.Prof_ID as prof_id",
                "prof.profile_picture as profile_picture",
                "prof.Dept_ID as dept_id",
                DB::raw("lm.last_message_time"),
                DB::raw("lm.last_message"),
                DB::raw("lm.last_sender"),
                DB::raw("COALESCE(elig.can_video_call, 0) as can_video_call"),
                DB::raw("COALESCE(elig.meeting_link, '') as meeting_link"),
                DB::raw("COALESCE(elig.booking_date, '') as booking_date"),
                DB::raw("COALESCE(elig.booking_time, '') as booking_time"),
                DB::raw("COALESCE(elig.student_count, 0) as schedule_students"),
            ])
            ->where("prof.Dept_ID", 2)
            ->orderBy("prof.Name")
            ->get();

        $professors = $professors->map(function ($professor) {
            $normalizedPhoto = ProfilePhotoPath::normalize($professor->profile_picture ?? null);
            $professor->profile_picture = $normalizedPhoto;
            $professor->profile_photo_url = ProfilePhotoPath::url($normalizedPhoto);
            if (
                config("app.debug") &&
                (int) ($professor->prof_id ?? 0) === 3001 &&
                empty($professor->meeting_link)
            ) {
                $professor->meeting_link = "demo-group-call-prof-3001";
            }
            $channel = $this->buildScheduleChannel(
                (int) ($professor->prof_id ?? 0),
                $professor->meeting_link ?? "",
                $professor->booking_date ?? null,
                $professor->booking_time ?? null,
            );
            $professor->schedule_channel = $channel;
            if (empty($professor->meeting_link)) {
                $professor->meeting_link = $channel;
            }
            return $professor;
        });

        return view("messages", compact("professors"));
    }

    public function showProfessorMessages()
    {
        $user = Auth::guard("professor")->user();
        if (!$user) {
            return redirect()
                ->route("login")
                ->with("error", "Please log in as a professor to view messages.");
        }

        // Determine which students are eligible for video call TODAY (approved/rescheduled bookings)
        $now = now("Asia/Manila");
        $todayPad = $now->format("D M d Y");
        $todayNoPad = $now->format("D M j Y");
        $todayIso = $now->toDateString();
        $capacityStatuses = ["approved", "rescheduled"];
        $meetingLinkColumn = $this->detectColumn("t_consultation_bookings", [
            "Meeting_Link",
            "meeting_link",
        ]);
        $bookingTimeColumn = $this->detectColumn("t_consultation_bookings", [
            "Booking_Time",
            "booking_time",
        ]);
        $modeColumn = $this->detectColumn("t_consultation_bookings", ["Mode", "mode"]);
        $meetingLinkExpr = $meetingLinkColumn ? "NULLIF(b.`{$meetingLinkColumn}`, '')" : "NULL";
        $bookingTimeExpr = $bookingTimeColumn ? "NULLIF(b.`{$bookingTimeColumn}`, '')" : "NULL";

        $eligibleToday = DB::table("t_consultation_bookings as b")
            ->select(
                "b.Stud_ID",
                DB::raw("1 as can_video_call"),
                DB::raw("MAX({$meetingLinkExpr}) as meeting_link"),
                DB::raw("MIN(NULLIF(b.Booking_Date, '')) as booking_date"),
                DB::raw("MIN({$bookingTimeExpr}) as booking_time"),
            )
            ->where("b.Prof_ID", $user->Prof_ID)
            ->whereIn("b.Status", $capacityStatuses)
            ->whereIn("b.Booking_Date", [$todayPad, $todayNoPad, $todayIso])
            ->groupBy("b.Stud_ID");

        // Direct messaging aggregation using Stud_ID/Prof_ID
        $students = DB::table("t_chat_messages as m")
            ->join("t_student as stu", "stu.Stud_ID", "=", "m.Stud_ID")
            ->leftJoinSub($eligibleToday, "elig", function ($join) {
                $join->on("elig.Stud_ID", "=", "stu.Stud_ID");
            })
            ->where("m.Prof_ID", $user->Prof_ID)
            ->select([
                "stu.Name as name",
                "stu.Stud_ID as stud_id",
                "stu.profile_picture as profile_picture",
                DB::raw("MAX(m.Created_At) as last_message_time"),
                DB::raw(
                    'SUBSTRING_INDEX(GROUP_CONCAT(m.Message ORDER BY m.Created_At DESC), ",", 1) as last_message',
                ),
                DB::raw(
                    'SUBSTRING_INDEX(GROUP_CONCAT(m.Sender ORDER BY m.Created_At DESC), ",", 1) as last_sender',
                ),
                DB::raw("COALESCE(elig.can_video_call, 0) as can_video_call"),
                DB::raw("COALESCE(elig.meeting_link, '') as meeting_link"),
                DB::raw("COALESCE(elig.booking_date, '') as booking_date"),
                DB::raw("COALESCE(elig.booking_time, '') as booking_time"),
            ])
            ->groupBy(
                "stu.Name",
                "stu.Stud_ID",
                "stu.profile_picture",
                "elig.can_video_call",
                "elig.meeting_link",
                "elig.booking_date",
                "elig.booking_time",
            )
            ->orderBy("last_message_time", "desc")
            ->get();

        $demoChannelOverride =
            config("app.debug") && (int) ($user->Prof_ID ?? 0) === 3001
                ? "demo-group-call-prof-3001"
                : null;

        $students = $students->map(function ($student) use ($user, $demoChannelOverride) {
            $normalizedPhoto = ProfilePhotoPath::normalize($student->profile_picture ?? null);
            $student->profile_picture = $normalizedPhoto;
            $student->profile_photo_url = ProfilePhotoPath::url($normalizedPhoto);
            if ($demoChannelOverride && empty($student->meeting_link)) {
                $student->meeting_link = $demoChannelOverride;
            }
            $channel = $this->buildScheduleChannel(
                (int) ($user->Prof_ID ?? 0),
                $student->meeting_link ?? "",
                $student->booking_date ?? null,
                $student->booking_time ?? null,
            );
            $student->schedule_channel = $channel;
            if ((int) ($student->can_video_call ?? 0) === 1 && empty($student->meeting_link)) {
                $student->meeting_link = $channel;
            }
            return $student;
        });

        $todaySchedules = [];
        $scheduleSelect = [
            DB::raw("NULLIF(b.Booking_Date, '') as booking_date"),
            DB::raw("b.Stud_ID as stud_id"),
        ];
        $scheduleSelect[] = DB::raw(
            $meetingLinkColumn
                ? "NULLIF(b.`{$meetingLinkColumn}`, '') as meeting_link"
                : "NULL as meeting_link",
        );
        $scheduleSelect[] = DB::raw(
            $bookingTimeColumn
                ? "NULLIF(b.`{$bookingTimeColumn}`, '') as booking_time"
                : "NULL as booking_time",
        );
        $scheduleSelect[] = DB::raw(
            $modeColumn ? "NULLIF(b.`{$modeColumn}`, '') as mode" : "NULL as mode",
        );

        $bookingsToday = DB::table("t_consultation_bookings as b")
            ->select($scheduleSelect)
            ->where("b.Prof_ID", $user->Prof_ID)
            ->whereIn("b.Status", $capacityStatuses)
            ->whereIn("b.Booking_Date", [$todayPad, $todayNoPad, $todayIso])
            ->orderBy("b.Booking_Date")
            ->when($bookingTimeColumn, function ($query) use ($bookingTimeColumn) {
                return $query->orderBy("b.{$bookingTimeColumn}");
            })
            ->get();

        $buckets = [];
        foreach ($bookingsToday as $entry) {
            $meetingLink = $entry->meeting_link ?? "";
            if ($demoChannelOverride && trim((string) $meetingLink) === "") {
                $meetingLink = $demoChannelOverride;
            }
            $channel = $this->buildScheduleChannel(
                (int) ($user->Prof_ID ?? 0),
                $meetingLink,
                $entry->booking_date ?? null,
                $entry->booking_time ?? null,
            );
            if (!isset($buckets[$channel])) {
                $buckets[$channel] = [
                    "channel" => $channel,
                    "booking_date" => $entry->booking_date,
                    "booking_time" => $entry->booking_time,
                    "mode" => $entry->mode ?? null,
                    "student_ids" => [],
                ];
            }
            if (!empty($entry->stud_id)) {
                $buckets[$channel]["student_ids"][] = $entry->stud_id;
            }
            $existingTime = $buckets[$channel]["booking_time"] ?? null;
            if (
                (string) ($entry->booking_time ?? "") !== "" &&
                ($existingTime === null ||
                    $existingTime === "" ||
                    strcmp((string) $entry->booking_time, (string) $existingTime) < 0)
            ) {
                $buckets[$channel]["booking_time"] = $entry->booking_time;
            }
        }

        foreach ($buckets as $bucket) {
            $todaySchedules[] = [
                "channel" => $bucket["channel"],
                "label" => $this->formatScheduleLabel(
                    $bucket["booking_date"] ?? null,
                    $bucket["booking_time"] ?? null,
                    count($bucket["student_ids"]),
                    $bucket["mode"] ?? null,
                ),
                "date" => $bucket["booking_date"],
                "time" => $bucket["booking_time"],
                "studentCount" => count($bucket["student_ids"]),
                "mode" => $bucket["mode"],
            ];
        }

        usort($todaySchedules, function ($a, $b) {
            $timeA = $a["time"] ?? "";
            $timeB = $b["time"] ?? "";
            return strcmp((string) $timeA, (string) $timeB);
        });

        return view("messages-professor", [
            "students" => $students,
            "todaySchedules" => $todaySchedules,
        ]);
    }

    public function loadMessages($bookingId)
    {
        $messages = ChatMessage::where("Booking_ID", $bookingId)
            ->orderBy("Created_At", "asc")
            ->get()
            ->map(function ($msg) {
                // Convert to Asia/Manila and ISO8601
                $msg->created_at_iso = \Carbon\Carbon::parse($msg->Created_At)
                    ->timezone("Asia/Manila")
                    ->toIso8601String();
                return $msg;
            });

        return response()->json($messages);
    }

    // New endpoint: load messages for student/professor pair (booking independent)
    public function loadDirectMessages($studId, $profId)
    {
        $hasIsRead = Schema::hasColumn("t_chat_messages", "is_read");
        try {
            // Mark messages from counterpart to current viewer as read (only if column exists)
            if ($hasIsRead) {
                if (Auth::check() && optional(Auth::user())->Stud_ID == (int) $studId) {
                    ChatMessage::betweenParticipants($studId, $profId)
                        ->where("Sender", "!=", "student")
                        ->where("is_read", 0)
                        ->update(["is_read" => 1]);
                } elseif (
                    Auth::guard("professor")->check() &&
                    optional(Auth::guard("professor")->user())->Prof_ID == (int) $profId
                ) {
                    ChatMessage::betweenParticipants($studId, $profId)
                        ->where("Sender", "!=", "professor")
                        ->where("is_read", 0)
                        ->update(["is_read" => 1]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Read mark failed (likely missing is_read column): " . $e->getMessage());
        }

        $messages = ChatMessage::betweenParticipants($studId, $profId)
            ->orderBy("Created_At", "asc")
            ->get()
            ->map(function ($msg) {
                // Convert to Asia/Manila and ISO8601
                $msg->created_at_iso = \Carbon\Carbon::parse($msg->Created_At)
                    ->timezone("Asia/Manila")
                    ->toIso8601String();
                return $msg;
            });
        return response()->json($messages);
    }

    // Unread count for current student across professors
    public function unreadCountsStudent()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([]);
        }
        $query = ChatMessage::select("Prof_ID", DB::raw("COUNT(*) as unread"))
            ->where("Stud_ID", $user->Stud_ID)
            ->where("Sender", "!=", "student");
        if (Schema::hasColumn("t_chat_messages", "is_read")) {
            $query->where("is_read", 0);
        }
        $rows = $query->groupBy("Prof_ID")->get();
        return response()->json($rows);
    }

    // Unread count for current professor across students
    public function unreadCountsProfessor()
    {
        $user = Auth::guard("professor")->user();
        if (!$user) {
            return response()->json([]);
        }
        $query = ChatMessage::select("Stud_ID", DB::raw("COUNT(*) as unread"))
            ->where("Prof_ID", $user->Prof_ID)
            ->where("Sender", "!=", "professor");
        if (Schema::hasColumn("t_chat_messages", "is_read")) {
            $query->where("is_read", 0);
        }
        $rows = $query->groupBy("Stud_ID")->get();
        return response()->json($rows);
    }

    // Mark all messages from counterpart in a pair as read when viewer has the thread open
    public function markPairRead(\Illuminate\Http\Request $request)
    {
        $studId = (int) $request->input("stud_id");
        $profId = (int) $request->input("prof_id");
        if (!$studId || !$profId) {
            return response()->json(["ok" => false, "error" => "missing_ids"], 422);
        }
        if (!Schema::hasColumn("t_chat_messages", "is_read")) {
            return response()->json(["ok" => false, "error" => "no_is_read_column"]);
        }

        $viewerRole = null;
        if (Auth::check() && Auth::user()->Stud_ID == $studId) {
            $viewerRole = "student";
        } elseif (
            Auth::guard("professor")->check() &&
            Auth::guard("professor")->user()->Prof_ID == $profId
        ) {
            $viewerRole = "professor";
        }
        if (!$viewerRole) {
            return response()->json(["ok" => false, "error" => "unauthorized"], 403);
        }

        $query = ChatMessage::betweenParticipants($studId, $profId)
            ->where("Sender", "!=", $viewerRole)
            ->where("is_read", 0);
        $updated = $query->update(["is_read" => 1]);

        $lastReadId = null;
        $lastCreatedAt = null;
        if ($updated > 0) {
            // Try to detect an id column dynamically to avoid 500 if schema differs
            $idColumn = null;
            foreach (["Message_ID", "message_id", "ID", "id", "ChatMessage_ID"] as $cand) {
                if (\Illuminate\Support\Facades\Schema::hasColumn("t_chat_messages", $cand)) {
                    $idColumn = $cand;
                    break;
                }
            }
            $base = ChatMessage::betweenParticipants($studId, $profId)->where(
                "Sender",
                "!=",
                $viewerRole,
            );
            if ($idColumn) {
                $lastReadId = $base->max($idColumn);
            }
            // Always fetch last Created_At for potential ordering client side
            if (\Illuminate\Support\Facades\Schema::hasColumn("t_chat_messages", "Created_At")) {
                $lastCreatedAt = ChatMessage::betweenParticipants($studId, $profId)
                    ->where("Sender", "!=", $viewerRole)
                    ->max("Created_At");
            }
            try {
                $lastCreatedAtIso = $lastCreatedAt
                    ? \Carbon\Carbon::parse($lastCreatedAt, "Asia/Manila")->toIso8601String()
                    : null;
                event(
                    new \App\Events\PairRead(
                        $studId,
                        $profId,
                        $viewerRole,
                        $lastReadId,
                        $lastCreatedAtIso,
                    ),
                );
            } catch (\Throwable $e) {
                /* silent */
            }
        }
        return response()->json([
            "ok" => true,
            "updated" => $updated,
            "last_read_message_id" => $lastReadId,
            "last_created_at" => $lastCreatedAt,
        ]);
    }

    // Presence ping
    public function presencePing(Request $request)
    {
        $now = now("Asia/Manila");
        if (Auth::check()) {
            DB::table("chat_presences")->upsert(
                [
                    "Stud_ID" => Auth::user()->Stud_ID,
                    "Prof_ID" => null,
                    "last_seen_at" => $now,
                ],
                ["Stud_ID", "Prof_ID"],
                ["last_seen_at"],
            );
            // Broadcast immediate presence update
            event(new PresencePing("student", (int) Auth::user()->Stud_ID));
        }
        if (Auth::guard("professor")->check()) {
            DB::table("chat_presences")->upsert(
                [
                    "Stud_ID" => null,
                    "Prof_ID" => Auth::guard("professor")->user()->Prof_ID,
                    "last_seen_at" => $now,
                ],
                ["Stud_ID", "Prof_ID"],
                ["last_seen_at"],
            );
            event(new PresencePing("professor", (int) Auth::guard("professor")->user()->Prof_ID));
        }
        return response()->json(["ok" => true]);
    }

    public function onlineLists()
    {
        $cutoff = now("Asia/Manila")->subMinutes(3); // 3-minute activity window
        $students = DB::table("chat_presences")
            ->whereNotNull("Stud_ID")
            ->where("last_seen_at", ">=", $cutoff)
            ->pluck("Stud_ID");
        $professors = DB::table("chat_presences")
            ->whereNotNull("Prof_ID")
            ->where("last_seen_at", ">=", $cutoff)
            ->pluck("Prof_ID");
        return response()->json(["students" => $students, "professors" => $professors]);
    }

    public function typing(Request $request)
    {
        $request->validate([
            "stud_id" => "required|numeric",
            "prof_id" => "required|numeric",
            "sender" => "required|in:student,professor",
            "is_typing" => "required|boolean",
        ]);
        event(
            new \App\Events\TypingIndicator(
                $request->stud_id,
                $request->prof_id,
                $request->sender,
                $request->is_typing,
            ),
        );
        return response()->json(["ok" => true]);
    }

    // Minimal student summary for realtime inbox creation on professor side
    public function studentSummary($studId)
    {
        // Restrict to authenticated professor to prevent information leakage
        if (!Auth::guard("professor")->check()) {
            return response()->json(["error" => "forbidden"], 403);
        }
        $row = DB::table("t_student as stu")
            ->select([
                "stu.Stud_ID as stud_id",
                "stu.Name as name",
                "stu.profile_picture as profile_picture",
            ])
            ->where("stu.Stud_ID", (int) $studId)
            ->first();
        if (!$row) {
            return response()->json(["error" => "not_found"], 404);
        }

        $normalizedPhoto = ProfilePhotoPath::normalize($row->profile_picture ?? null);
        $row->profile_picture = $normalizedPhoto;
        $row->profile_photo_url = ProfilePhotoPath::url($normalizedPhoto);

        return response()->json($row);
    }

    private function detectColumn(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) {
            return null;
        }
        $columns = Schema::getColumnListing($table);
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return null;
    }

    private function buildScheduleChannel(
        int $profId,
        $meetingLink,
        $bookingDate,
        $bookingTime,
    ): string {
        $link = trim((string) ($meetingLink ?? ""));
        if ($link !== "") {
            return $link;
        }
        $dateKey = trim((string) ($bookingDate ?? ""));
        $timeKey = trim((string) ($bookingTime ?? ""));
        $raw = $profId . "|" . $dateKey . "|" . $timeKey;
        if ($raw === "0||") {
            return "schedule-prof-" . $profId;
        }
        $hash = substr(sha1($raw), 0, 12);
        return "schedule-prof-{$profId}-{$hash}";
    }

    private function formatScheduleLabel(
        $bookingDate,
        $bookingTime,
        int $count,
        $mode = null,
    ): string {
        $parts = [];
        $dateStr = trim((string) ($bookingDate ?? ""));
        if ($dateStr !== "") {
            $parts[] = $this->humanizeDate($dateStr);
        }
        $timeStr = trim((string) ($bookingTime ?? ""));
        if ($timeStr !== "") {
            $parts[] = $this->humanizeTime($timeStr);
        }
        $modeStr = trim((string) ($mode ?? ""));
        if ($modeStr !== "") {
            $parts[] = ucfirst(strtolower($modeStr));
        }
        if ($count > 0) {
            $parts[] = $count === 1 ? "1 student" : $count . " students";
        }
        $parts = array_values(array_filter($parts, fn($part) => $part !== ""));
        $label = implode(" | ", $parts);
        return $label !== "" ? $label : "Class call";
    }

    private function humanizeDate(string $value): string
    {
        try {
            return Carbon::parse($value, "Asia/Manila")->isoFormat("MMM D, YYYY");
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function humanizeTime(string $value): string
    {
        $raw = trim($value);
        if ($raw === "") {
            return "";
        }
        $formats = ["H:i:s", "H:i", "g:i A", "g:i a"];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $raw, "Asia/Manila")->format("g:i A");
            } catch (\Throwable $e) {
                // continue
            }
        }
        try {
            return Carbon::parse($raw, "Asia/Manila")->format("g:i A");
        } catch (\Throwable $e) {
            return $raw;
        }
    }
}
