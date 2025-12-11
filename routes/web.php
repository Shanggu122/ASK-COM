<?php

use App\Http\Controllers\ChatBotController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfessorProfileController;
use App\Http\Controllers\ConsultationBookingController;
use App\Http\Controllers\ConsultationLogController;
use App\Http\Controllers\VideoCallController;
use App\Http\Controllers\ProfVideoCallController;
use App\Http\Controllers\VideoCallChatController;
// use App\Http\Controllers\CallPresenceController; // Temporarily disabled (controller file missing)
use App\Http\Controllers\AuthControllerProfessor;
use App\Http\Controllers\ConsultationLogControllerProfessor;
use App\Http\Controllers\ConsultationBookingControllerProfessor;
use App\Http\Controllers\AdminAcademicTermController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\UnifiedAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\comsciController;
use App\Http\Controllers\ProfessorComSciController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CardComsci;
use App\Models\Professor;
use App\Models\Notification;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfessorConsultationPdfController;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // added for availability endpoint
use Carbon\CarbonPeriod;
use Database\Seeders\DemoMeetingSeeder;
use Database\Seeders\DemoStudentSeeder;

Route::get("/", [UnifiedAuthController::class, "showLoginForm"])->name("login");

// OTP password reset routes
use App\Http\Controllers\PasswordOtpController;
Route::get("/forgotpassword", function () {
    return view("forgotpassword");
})->name("forgotpassword");
Route::post("/forgotpassword/send", [PasswordOtpController::class, "sendOtp"])->name(
    "forgotpassword.send",
);
Route::get("/verify-otp", [PasswordOtpController::class, "showVerifyForm"])->name(
    "otp.verify.form",
);
Route::post("/verify-otp", [PasswordOtpController::class, "verifyOtp"])->name("otp.verify");
Route::get("/resend-otp", [PasswordOtpController::class, "resendOtp"])->name("otp.resend");
Route::get("/reset-password", [PasswordOtpController::class, "showResetForm"])->name(
    "password.reset.form",
);
Route::post("/reset-password", [PasswordOtpController::class, "updatePassword"])->name(
    "password.update",
);

// Unified login page
Route::get("/login", [UnifiedAuthController::class, "showLoginForm"]);

// Unified login POST route
Route::post("/login", [UnifiedAuthController::class, "login"])->name("login.submit");
// Protected student routes (require authentication)
Route::get("/dashboard", function () {
    return view("dashboard"); // student dashboard
})
    ->name("dashboard")
    ->middleware(["auth", \App\Http\Middleware\PreventBackHistory::class]);

// Legacy /itis & /comsci definitions removed here; final definitions below also protected.

// Remove duplicate unprotected /profile route; handled in auth group below.

Route::get("/conlog", [ConsultationLogController::class, "index"])
    ->name("consultation-log")
    ->middleware(["auth", \App\Http\Middleware\PreventBackHistory::class]);

// Messages route handled inside auth group below; public definition removed.

// routes/web.php

Route::middleware(["auth", \App\Http\Middleware\PreventBackHistory::class])->group(function () {
    Route::get("/profile", [ProfileController::class, "show"])->name("profile.show");
    Route::post("/change-password", [AuthController::class, "changePassword"])->name(
        "changePassword",
    );
    Route::get("/messages", [MessageController::class, "showMessages"])->name("messages");
});

Route::get("/logout", [AuthController::class, "logout"])->name("logout");

use App\Services\DialogflowService;

Route::post("/chat", [App\Http\Controllers\ChatBotController::class, "chat"]);

Route::post("/consultation-book", [ConsultationBookingController::class, "store"])->name(
    "consultation-book",
);

Route::get("/get-bookings", [ConsultationLogController::class, "getBookings"]);

// Professor protected pages (ensure no back history caching after logout)
Route::middleware([
    \App\Http\Middleware\EnsureProfessorAuthenticated::class,
    \App\Http\Middleware\PreventBackHistory::class,
])->group(function () {
    Route::get("/profile-professor", [ProfessorProfileController::class, "show"])->name(
        "profile.professor",
    );
    // Professor messages page (secured)
    Route::get("/messages-professor", [MessageController::class, "showProfessorMessages"])->name(
        "messages.professor",
    );
    // Export professor consultation logs to PDF
    Route::post("/conlog-professor/pdf", [
        ProfessorConsultationPdfController::class,
        "download",
    ])->name("conlog-professor.pdf");
    Route::post("/profile-professor/change-password", [
        AuthControllerProfessor::class,
        "changePassword",
    ])->name("changePassword.professor");
    Route::get("/comsci-professor", [ProfessorComSciController::class, "showColleagues"])->name(
        "comsci-professor",
    );
    // Add other professor-only routes here
});

// dynamic "video-call" page — {user} will be the channel name (students only)
Route::get("/video-call/{user}", [VideoCallController::class, "show"])
    ->name("video.call")
    ->middleware("auth");

Route::get("/video-call/participants/{uid}", [VideoCallController::class, "participant"])->name(
    "video.call.participant",
);

Route::get("/video-call/chat/history", [VideoCallChatController::class, "history"])->name(
    "video.call.chat.history",
);
Route::post("/video-call/chat", [VideoCallChatController::class, "store"])->name(
    "video.call.chat.store",
);

if (config("app.debug")) {
    Route::get("/dev/demo-setup", function () {
        app(DemoMeetingSeeder::class)->run();

        return response()->json([
            "students" => [
                ["Stud_ID" => "910000001", "password" => "demo1234"],
                ["Stud_ID" => "910000002", "password" => "demo1234"],
                ["Stud_ID" => "910000003", "password" => "demo1234"],
            ],
            "professor" => ["Prof_ID" => 3001, "password" => "demo1234"],
            "meeting_channel" => "stud-910000001-prof-3001",
        ]);
    })->name("demo.setup");

    Route::get("/dev/video-call-demo", function (Request $request) {
        $defaults = [
            "mock" => max(0, (int) $request->query("mock", 0)),
            "mockNames" => $request->query("mockNames", "Anna|Ben|Carla"),
            "role" => $request->query("role", "student"),
            "seed" => $request->query("seed", "1"),
            "channel" => $request->query("channel", "stud-910000001-prof-3001"),
        ];

        if (!$request->has("mock")) {
            return redirect($request->fullUrlWithQuery($defaults));
        }

        if ($request->query("seed", "1") !== "0") {
            app(DemoMeetingSeeder::class)->run();
        } else {
            app(DemoStudentSeeder::class)->run();
        }

        $role = strtolower($defaults["role"]);
        $isProfessor = $role === "professor";

        $channel = $defaults["channel"];
        $counterpart = $isProfessor ? "Demo Student" : "Demo Professor";

        return view("video-call", [
            "channel" => $channel,
            "counterpartName" => $counterpart,
        ]);
    })->name("video.call.demo");
}

// Agora token issuance (student only)
Route::middleware(["web", "auth"])->group(function () {
    Route::get("/agora/token/rtc", [
        \App\Http\Controllers\AgoraTokenController::class,
        "rtcToken",
    ])->name("agora.token.rtc");
    Route::get("/agora/token/rtm", [
        \App\Http\Controllers\AgoraTokenController::class,
        "rtmToken",
    ])->name("agora.token.rtm");
});

// Agora token issuance (professor only)
Route::middleware(["web", \App\Http\Middleware\EnsureProfessorAuthenticated::class])->group(
    function () {
        Route::get("/agora/token/rtc-prof", [
            \App\Http\Controllers\AgoraTokenController::class,
            "rtcTokenProfessor",
        ])->name("agora.token.rtc.prof");
        Route::get("/agora/token/rtm-prof", [
            \App\Http\Controllers\AgoraTokenController::class,
            "rtmTokenProfessor",
        ])->name("agora.token.rtm.prof");
    },
);
// Presence limiting endpoints (max 5 students per channel)
// Route placeholders disabled until CallPresenceController is added
// (CallPresenceController routes temporarily removed until controller implementation is added)

Route::get("/prof-call/{channel}", [ProfVideoCallController::class, "show"]);

Route::get("/dashboard-professor", function () {
    return view("dashboard-professor");
})
    ->middleware([
        \App\Http\Middleware\EnsureProfessorAuthenticated::class,
        \App\Http\Middleware\PreventBackHistory::class,
    ])
    ->name("dashboard.professor");

Route::get("/conlog-professor", [ConsultationLogControllerProfessor::class, "index"])
    ->name("conlog-professor")
    ->middleware([
        \App\Http\Middleware\EnsureProfessorAuthenticated::class,
        \App\Http\Middleware\PreventBackHistory::class,
    ]);

Route::post("/consultation-book-professor", [
    ConsultationBookingControllerProfessor::class,
    "store",
])->name("consultation-book.professor");

Route::get("/logout-professor", [AuthControllerProfessor::class, "logout"])->name(
    "logout-professor",
);
Route::post("/change-password-professor", [AuthControllerProfessor::class, "changePassword"])->name(
    "changePassword.professor",
);

// (Removed duplicate unprotected /messages-professor route; now secured inside professor auth group)

Route::get("/user/{id}", [UserController::class, "getUserData"]);
Route::get("/user/{id}", [ProfessorController::class, "getUserData"]);

Route::post("/change-password-professor", [
    ProfessorProfileController::class,
    "changePassword",
])->middleware("auth");

Route::get("/api/consul", [ConsultationLogController::class, "apiBookings"]);

Route::get("/api/consultations", [ConsultationLogControllerProfessor::class, "apiBookings"]);

// API endpoints for consultation logs (real-time updates)
Route::get("/api/student/consultation-logs", [ConsultationLogController::class, "getBookings"]);
Route::get("/api/professor/consultation-logs", [
    ConsultationLogControllerProfessor::class,
    "getBookings",
]);

Route::post("/consultation-book-professor", [ConsultationBookingController::class, "store"])->name(
    "consultation-book.professor",
);

// Return dates (next 30 days) that are fully booked (>=5 approved/rescheduled) for a professor
Route::get("/api/professor/fully-booked-dates", function (\Illuminate\Http\Request $request) {
    try {
        $profId = auth()->guard("professor")->check()
            ? auth()->guard("professor")->user()->Prof_ID
            : $request->query("prof_id");
        if (!$profId) {
            return response()->json(
                ["success" => false, "message" => "Professor not identified"],
                401,
            );
        }
        $today = \Carbon\Carbon::now("Asia/Manila")->startOfDay();
        $end = $today->copy()->addDays(30);
        $capacityStatuses = ["approved", "rescheduled", "completion_pending"];
        $rows = DB::table("t_consultation_bookings")
            ->select("Booking_Date", DB::raw("COUNT(*) as cnt"))
            ->where("Prof_ID", $profId)
            ->whereBetween("Booking_Date", [$today->format("D M d Y"), $end->format("D M d Y")])
            ->whereIn("Status", $capacityStatuses)
            ->groupBy("Booking_Date")
            ->havingRaw("COUNT(*) >= 5")
            ->get();
        $dates = $rows->pluck("Booking_Date");
        return response()->json(["success" => true, "dates" => $dates]);
    } catch (\Exception $e) {
        return response()->json(
            ["success" => false, "message" => "Server error", "error" => $e->getMessage()],
            500,
        );
    }
});

// Availability endpoint: returns booked & remaining slots per day (approved/rescheduled only) within a date range
// Additionally returns the day's locked consultation mode ("online"/"onsite") if at least one
// approved/rescheduled booking exists for that date for the same professor. The lock is determined
// by the earliest created booking among the approved/rescheduled set for that date.
Route::get("/api/professor/availability", function (\Illuminate\Http\Request $request) {
    try {
        $profId = $request->query("prof_id");
        if (!$profId) {
            // if authenticated professor w/out prof_id param fallback
            $profId = auth()->guard("professor")->check()
                ? auth()->guard("professor")->user()->Prof_ID
                : null;
        }
        if (!$profId) {
            return response()->json(["success" => false, "message" => "prof_id required"], 422);
        }

        $capacity = 5; // daily capacity per professor (could be moved to config later)
        $startParam = $request->query("start");
        $endParam = $request->query("end");
        $tz = "Asia/Manila";
        try {
            $start = $startParam
                ? Carbon::parse($startParam, $tz)->startOfDay()
                : Carbon::now($tz)->startOfMonth();
        } catch (\Exception $e) {
            $start = Carbon::now($tz)->startOfMonth();
        }
        try {
            $end = $endParam
                ? Carbon::parse($endParam, $tz)->endOfDay()
                : $start->copy()->addMonths(1)->endOfMonth();
        } catch (\Exception $e) {
            $end = $start->copy()->addMonths(1)->endOfMonth();
        }

        // Hard cap on range (max 90 days) to avoid excessive payload
        if ($end->diffInDays($start) > 90) {
            $end = $start->copy()->addDays(90)->endOfDay();
        }

        $capacityStatuses = ["approved", "rescheduled", "completion_pending"];

        // Build explicit list of date strings to avoid lexicographical issues with whereBetween on string dates
        $dateStrings = [];
        foreach (CarbonPeriod::create($start, $end) as $d) {
            $dateStrings[] = $d->format("D M d Y");
        }

        $rows = DB::table("t_consultation_bookings")
            ->select("Booking_Date", DB::raw("COUNT(*) as cnt"))
            ->where("Prof_ID", $profId)
            ->whereIn("Status", $capacityStatuses)
            ->whereIn("Booking_Date", $dateStrings)
            ->groupBy("Booking_Date")
            ->get()
            ->pluck("cnt", "Booking_Date");

        // Determine per-day mode lock using the earliest-created approved/rescheduled booking
        $firstIds = DB::table("t_consultation_bookings")
            ->select("Booking_Date", DB::raw("MIN(Booking_ID) as first_id"))
            ->where("Prof_ID", $profId)
            ->whereIn("Status", $capacityStatuses)
            ->whereIn("Booking_Date", $dateStrings)
            ->groupBy("Booking_Date")
            ->get();
        $firstIdByDate = [];
        foreach ($firstIds as $rec) {
            $firstIdByDate[$rec->Booking_Date] = $rec->first_id;
        }
        $modesById = [];
        if (!empty($firstIdByDate)) {
            $modesById = DB::table("t_consultation_bookings")
                ->whereIn("Booking_ID", array_values($firstIdByDate))
                ->pluck("Mode", "Booking_ID")
                ->toArray();
        }
        $modeLockByDate = [];
        foreach ($firstIdByDate as $d => $id) {
            if (isset($modesById[$id])) {
                $modeLockByDate[$d] = $modesById[$id];
            }
        }

        $dates = [];
        $overrideSvc = app(\App\Services\CalendarOverrideService::class);
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $key = $day->format("D M d Y");
            $booked = $rows[$key] ?? 0;
            $remaining = max($capacity - $booked, 0);
            $mode = $modeLockByDate[$key] ?? null; // 'online' | 'onsite' | null (unlocked)
            $ov = $overrideSvc->evaluate((int) $profId, $key);
            $dates[] = [
                "date" => $key,
                "booked" => $booked,
                "remaining" => $remaining,
                "mode" => $mode,
                "blocked" => $ov["blocked"] ?? false,
                "forced_mode" => $ov["forced_mode"] ?? null,
            ];
        }

        return response()->json([
            "success" => true,
            "capacity" => $capacity,
            "dates" => $dates,
        ]);
    } catch (\Exception $e) {
        return response()->json(
            ["success" => false, "message" => "Server error", "error" => $e->getMessage()],
            500,
        );
    }
});

// Signed email action routes (professor can act directly from email)
Route::get("/email-action/consultations/{bookingId}/{profId}/accept", [
    \App\Http\Controllers\ConsultationEmailActionController::class,
    "accept",
])
    ->name("consultation.email.accept")
    ->middleware("signed");
Route::get("/email-action/consultations/{bookingId}/{profId}/reschedule", [
    \App\Http\Controllers\ConsultationEmailActionController::class,
    "rescheduleForm",
])
    ->name("consultation.email.reschedule.form")
    ->middleware("signed");
Route::post("/email-action/consultations/{bookingId}/{profId}/reschedule", [
    \App\Http\Controllers\ConsultationEmailActionController::class,
    "rescheduleSubmit",
])
    ->name("consultation.email.reschedule.submit")
    ->middleware("signed");

Route::post("/api/consultations/update-status", function (Request $request) {
    try {
        $id = $request->input("id");
        $status = strtolower(trim((string) $request->input("status")));
        $newMode = $request->input("mode"); // optional; current or target mode (if UI sends it)
        $newDate = $request->input("new_date"); // For rescheduling
        $rescheduleReason = $request->input("reschedule_reason"); // For reschedule reason
        $completionReason = $request->input("completion_reason");
        $completionStudentComment = $request->input("completion_student_comment");

        $studentUser = Auth::user();
        $professorUser = Auth::guard("professor")->user();

        // Validate inputs
        if (!$id) {
            return response()->json([
                "success" => false,
                "message" => "Booking ID is required.",
            ]);
        }

        if (!$status) {
            return response()->json([
                "success" => false,
                "message" => "Status is required.",
            ]);
        }
        $status = strtolower((string) $status);

        // Get the booking details before updating
        $booking = DB::table("t_consultation_bookings")
            ->leftJoin("professors", "t_consultation_bookings.Prof_ID", "=", "professors.Prof_ID")
            ->select(
                "t_consultation_bookings.*",
                "professors.Name as Prof_Name",
                "professors.Schedule as Prof_Schedule",
            )
            ->where("Booking_ID", $id)
            ->first();

        if (!$booking) {
            return response()->json([
                "success" => false,
                "message" => "No booking found for this ID.",
            ]);
        }

        $currentStatus = strtolower((string) ($booking->Status ?? ""));

        // Helper: parse allowed weekdays (Mon..Fri => 1..5) from professor Schedule field
        $parseAllowedDays = function (?string $scheduleText): array {
            if (!$scheduleText) {
                return [1, 2, 3, 4, 5]; // default Mon–Fri
            }
            $days = [];
            $map = [
                "monday" => 1,
                "tuesday" => 2,
                "wednesday" => 3,
                "thursday" => 4,
                "friday" => 5,
            ];
            foreach (preg_split('/\r?\n/', (string) $scheduleText) as $line) {
                if (!is_string($line)) {
                    continue;
                }
                if (preg_match("/\b(Monday|Tuesday|Wednesday|Thursday|Friday)\b/i", $line, $m)) {
                    $key = strtolower($m[1]);
                    if (isset($map[$key])) {
                        $days[$map[$key]] = true;
                    }
                }
            }
            $allowed = array_keys($days);
            return !empty($allowed) ? array_values($allowed) : [1, 2, 3, 4, 5];
        };

        // Helper: find next available consultation date for a professor after a given date
        $findNextAvailableDate = function (
            int $profId,
            string $fromDate,
            string $desiredMode,
            array $allowedDays,
        ) {
            $tz = "Asia/Manila";
            try {
                $start = Carbon::parse($fromDate, $tz)->addDay()->startOfDay();
            } catch (\Throwable $e) {
                $start = Carbon::now($tz)->startOfDay();
            }
            $capacityStatuses = ["approved", "rescheduled", "completion_pending"]; // consume capacity
            for ($i = 0; $i < 90; $i++) {
                // look ahead up to ~3 months
                $d = $start->copy()->addDays($i);
                // Skip weekends always
                if ($d->isWeekend()) {
                    continue;
                }
                // Respect professor weekly schedule if present (1=Mon..5=Fri)
                $dow = (int) $d->dayOfWeekIso; // 1..7
                if (!in_array($dow, $allowedDays, true)) {
                    continue;
                }

                // Overrides: blocked or forced mode mismatch
                try {
                    $key = $d->format("D M d Y");
                    $ov = app(\App\Services\CalendarOverrideService::class)->evaluate(
                        $profId,
                        $key,
                    );
                    if (!empty($ov["blocked"])) {
                        continue;
                    }
                    if (
                        !empty($ov["forced_mode"]) &&
                        strtolower($ov["forced_mode"]) !== strtolower($desiredMode)
                    ) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    // ignore override errors
                }

                // Capacity check
                $key = $d->format("D M d Y");
                $cnt = DB::table("t_consultation_bookings")
                    ->where("Prof_ID", $profId)
                    ->where("Booking_Date", $key)
                    ->whereIn("Status", $capacityStatuses)
                    ->count();
                if ($cnt >= 5) {
                    continue;
                }

                // Mode-lock check: if the day already has first active booking with a mode, it must match
                $first = DB::table("t_consultation_bookings")
                    ->where("Prof_ID", $profId)
                    ->where("Booking_Date", $key)
                    ->whereIn("Status", $capacityStatuses)
                    ->orderBy("Booking_ID", "asc")
                    ->first();
                if (
                    $first &&
                    $first->Mode &&
                    strtolower($first->Mode) !== strtolower($desiredMode)
                ) {
                    continue;
                }

                return $key; // found
            }
            return null; // none within search window
        };

        // Update the status (with max 5 per day constraint for approve/reschedule).
        // Capacity counts only bookings already approved or rescheduled (pending does not consume a slot until approved/rescheduled).
        $capacityStatuses = ["approved", "rescheduled", "completion_pending"];
        $updateData = ["Status" => $status];
        if ($status === "rescheduled" && $newDate) {
            // Normalize new date (accept with or without commas, several formats)
            $rawInput = trim($newDate);
            $clean = str_replace(",", "", $rawInput);
            $carbon = null;
            $formats = ["D M d Y", "D M d Y H:i", "Y-m-d"];
            foreach ($formats as $fmt) {
                try {
                    $carbon = \Carbon\Carbon::createFromFormat($fmt, $clean, "Asia/Manila");
                    break;
                } catch (\Exception $e) {
                }
            }
            if (!$carbon) {
                try {
                    $carbon = \Carbon\Carbon::parse($clean, "Asia/Manila");
                } catch (\Exception $e) {
                    $carbon = null;
                }
            }
            if (!$carbon) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Invalid date format for reschedule.",
                    ],
                    422,
                );
            }
            $normalizedDate = $carbon->setTimezone("Asia/Manila")->startOfDay()->format("D M d Y");

            // Enforce override constraints first
            try {
                $ov = app(\App\Services\CalendarOverrideService::class)->evaluate(
                    (int) $booking->Prof_ID,
                    $normalizedDate,
                );
                if ($ov["blocked"] ?? false) {
                    return response()->json([
                        "success" => false,
                        "message" => "Cannot reschedule: date is blocked.",
                    ]);
                }
                if (!empty($ov["forced_mode"]) && $ov["forced_mode"] !== $booking->Mode) {
                    return response()->json([
                        "success" => false,
                        "message" =>
                            "Cannot reschedule: the date is restricted to " .
                            ucfirst($ov["forced_mode"]) .
                            " mode.",
                    ]);
                }
            } catch (\Throwable $e) {
            }

            // Enforce capacity: at most 5 active bookings (pending/approved/rescheduled) per professor per date
            $activeStatuses = $capacityStatuses; // only approved/rescheduled block capacity
            $existingCount = DB::table("t_consultation_bookings")
                ->where("Prof_ID", $booking->Prof_ID)
                ->where("Booking_Date", $normalizedDate)
                ->whereIn("Status", $activeStatuses)
                ->where("Booking_ID", "!=", $booking->Booking_ID)
                ->count();
            if ($existingCount >= 5) {
                return response()->json([
                    "success" => false,
                    "message" =>
                        "Cannot reschedule: selected date already has 5 approved/rescheduled bookings for this professor.",
                ]); // 200 with success false so frontend does not show generic network error
            }
            // Enforce mode lock on reschedule: if the target date is already locked, it must match the booking's original mode
            $firstExisting = DB::table("t_consultation_bookings")
                ->where("Prof_ID", $booking->Prof_ID)
                ->where("Booking_Date", $normalizedDate)
                ->whereIn("Status", $capacityStatuses)
                ->orderBy("Booking_ID", "asc")
                ->first();
            if ($firstExisting && $firstExisting->Mode && $booking->Mode !== $firstExisting->Mode) {
                return response()->json([
                    "success" => false,
                    "message" =>
                        "Cannot reschedule: the date is locked to " .
                        ucfirst($firstExisting->Mode) .
                        " mode.",
                ]);
            }
            $updateData["Booking_Date"] = $normalizedDate;
        }
        if ($status === "rescheduled" && $rescheduleReason) {
            $updateData["reschedule_reason"] = $rescheduleReason;
        }

        if ($status === "completion_pending") {
            if (!$professorUser || (int) $professorUser->Prof_ID !== (int) $booking->Prof_ID) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Only the assigned professor can request completion review.",
                    ],
                    403,
                );
            }

            if (
                !in_array($currentStatus, ["approved", "rescheduled", "completion_declined"], true)
            ) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Completion review can only be requested for approved consultations.",
                    ],
                    422,
                );
            }

            $reason = trim((string) $completionReason);
            $reason = strip_tags($reason ?? "");
            $reason = preg_replace("/\s+/u", " ", $reason);
            if (mb_strlen($reason) < 5) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Please provide a short explanation (minimum 5 characters).",
                    ],
                    422,
                );
            }
            if (mb_strlen($reason) > 1000) {
                $reason = mb_substr($reason, 0, 1000);
            }

            $updateData["completion_reason"] = $reason;
            $updateData["completion_requested_at"] = Carbon::now("Asia/Manila");
            $updateData["completion_reviewed_at"] = null;
            $updateData["completion_student_response"] = null;
            $updateData["completion_student_comment"] = null;

            $booking->completion_reason = $reason;
            $booking->completion_requested_at = $updateData["completion_requested_at"];
            $booking->completion_reviewed_at = null;
            $booking->completion_student_response = null;
            $booking->completion_student_comment = null;
            $booking->Status = "completion_pending";
        } elseif ($status === "completed") {
            if (!$studentUser || (int) ($studentUser->Stud_ID ?? 0) !== (int) $booking->Stud_ID) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Only the student who booked can confirm completion.",
                    ],
                    403,
                );
            }

            if ($currentStatus !== "completion_pending") {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Completion can only be confirmed after a professor request.",
                    ],
                    422,
                );
            }

            $comment = trim((string) $completionStudentComment);
            $comment = strip_tags($comment ?? "");
            if (mb_strlen($comment) > 1000) {
                $comment = mb_substr($comment, 0, 1000);
            }

            $updateData["completion_reviewed_at"] = Carbon::now("Asia/Manila");
            $updateData["completion_student_response"] = "agreed";
            $updateData["completion_student_comment"] = $comment !== "" ? $comment : null;

            $booking->completion_reviewed_at = $updateData["completion_reviewed_at"];
            $booking->completion_student_response = "agreed";
            $booking->completion_student_comment = $updateData["completion_student_comment"];
            $booking->Status = "completed";
        } elseif ($status === "completion_declined") {
            if (!$studentUser || (int) ($studentUser->Stud_ID ?? 0) !== (int) $booking->Stud_ID) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Only the student who booked can decline the completion request.",
                    ],
                    403,
                );
            }

            if ($currentStatus !== "completion_pending") {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Completion can only be declined when a review is pending.",
                    ],
                    422,
                );
            }

            $comment = trim((string) $completionStudentComment);
            $comment = strip_tags($comment ?? "");
            if (mb_strlen($comment) > 1000) {
                $comment = mb_substr($comment, 0, 1000);
            }

            $updateData["completion_reviewed_at"] = Carbon::now("Asia/Manila");
            $updateData["completion_student_response"] = "declined";
            $updateData["completion_student_comment"] = $comment !== "" ? $comment : null;

            $booking->completion_reviewed_at = $updateData["completion_reviewed_at"];
            $booking->completion_student_response = "declined";
            $booking->completion_student_comment = $updateData["completion_student_comment"];
            $booking->Status = "completion_declined";
        }

        // Enforce capacity when approving a pending booking (if switching to approved)
        if ($status === "approved") {
            $currentDate = $booking->Booking_Date; // existing date retained
            $existingApproved = DB::table("t_consultation_bookings")
                ->where("Prof_ID", $booking->Prof_ID)
                ->where("Booking_Date", $currentDate)
                ->whereIn("Status", $capacityStatuses)
                ->where("Booking_ID", "!=", $booking->Booking_ID)
                ->count();
            if ($existingApproved >= 5) {
                return response()->json([
                    "success" => false,
                    "message" =>
                        "Cannot approve: that date already has 5 approved/rescheduled bookings.",
                ]); // 200 JSON business rule violation
            }
            // Mode-lock: if first approved/rescheduled exists, booking mode must match it
            $firstExisting = DB::table("t_consultation_bookings")
                ->where("Prof_ID", $booking->Prof_ID)
                ->where("Booking_Date", $currentDate)
                ->whereIn("Status", $capacityStatuses)
                ->orderBy("Booking_ID", "asc")
                ->first();
            if ($firstExisting && $firstExisting->Mode && $booking->Mode !== $firstExisting->Mode) {
                return response()->json([
                    "success" => false,
                    "message" =>
                        "Cannot approve: the date is locked to " .
                        ucfirst($firstExisting->Mode) .
                        " mode.",
                ]);
            }
            // Flag: this approval will become the first active booking on this date (sets the mode lock)
            $willBeFirstActive = $existingApproved === 0;
        }

        $updated = DB::table("t_consultation_bookings")
            ->where("Booking_ID", $id)
            ->update($updateData);

        if ($updated > 0) {
            // Update existing notification instead of creating new one
            $professorName = $booking->Prof_Name;
            $date = $status === "rescheduled" && $newDate ? $newDate : $booking->Booking_Date;

            // Map internal status to notification type
            $notificationType = $status;
            if ($status === "approved") {
                $notificationType = "accepted";
            }

            $notificationContext = null;
            if ($status === "rescheduled") {
                $notificationContext = $rescheduleReason;
            } elseif ($status === "completion_pending") {
                $notificationContext =
                    $updateData["completion_reason"] ?? ($booking->completion_reason ?? null);
            } elseif ($status === "completed") {
                $notificationContext =
                    $updateData["completion_reason"] ?? ($booking->completion_reason ?? null);
            } elseif ($status === "completion_declined") {
                $notificationContext =
                    $updateData["completion_student_comment"] ??
                    ($completionStudentComment ?? null);
            }

            try {
                // Update existing notifications for this booking (both student and professor)
                Notification::updateNotificationStatus(
                    $id,
                    $notificationType,
                    $professorName,
                    $date,
                    $notificationContext,
                );
            } catch (\Exception $e) {
                // Don't fail the whole operation if notification fails
            }

            // Broadcast to professor's booking channel for live update on conlog-professor
            try {
                $profId = (int) $booking->Prof_ID;
                $completionRequestedAt =
                    $updateData["completion_requested_at"] ??
                    ($booking->completion_requested_at ?? null);
                $completionReviewedAt =
                    $updateData["completion_reviewed_at"] ??
                    ($booking->completion_reviewed_at ?? null);
                if ($completionRequestedAt instanceof Carbon) {
                    $completionRequestedAt = $completionRequestedAt->toDateTimeString();
                }
                if ($completionReviewedAt instanceof Carbon) {
                    $completionReviewedAt = $completionReviewedAt->toDateTimeString();
                }
                $completionReason =
                    $updateData["completion_reason"] ?? ($booking->completion_reason ?? null);
                $completionStudentResponse =
                    $updateData["completion_student_response"] ??
                    ($booking->completion_student_response ?? null);
                $completionStudentComment =
                    $updateData["completion_student_comment"] ??
                    ($booking->completion_student_comment ?? null);
                $payload = [
                    "event" => "BookingUpdated",
                    "Booking_ID" => (int) $booking->Booking_ID,
                    "Status" => $status,
                    "Booking_Date" => $updateData["Booking_Date"] ?? $booking->Booking_Date,
                    "reschedule_reason" => $updateData["reschedule_reason"] ?? null,
                    "completion_reason" => $completionReason,
                    "completion_requested_at" => $completionRequestedAt,
                    "completion_reviewed_at" => $completionReviewedAt,
                    "completion_student_response" => $completionStudentResponse,
                    "completion_student_comment" => $completionStudentComment,
                ];
                event(new \App\Events\BookingUpdated($profId, $payload));
            } catch (\Throwable $e) {
                // swallow broadcast errors
            }

            // Broadcast to student's booking channel for live update on student conlog
            try {
                $studId = (int) ($booking->Stud_ID ?? 0);
                if ($studId > 0) {
                    $completionRequestedAt =
                        $updateData["completion_requested_at"] ??
                        ($booking->completion_requested_at ?? null);
                    $completionReviewedAt =
                        $updateData["completion_reviewed_at"] ??
                        ($booking->completion_reviewed_at ?? null);
                    if ($completionRequestedAt instanceof Carbon) {
                        $completionRequestedAt = $completionRequestedAt->toDateTimeString();
                    }
                    if ($completionReviewedAt instanceof Carbon) {
                        $completionReviewedAt = $completionReviewedAt->toDateTimeString();
                    }
                    $completionReason =
                        $updateData["completion_reason"] ?? ($booking->completion_reason ?? null);
                    $completionStudentResponse =
                        $updateData["completion_student_response"] ??
                        ($booking->completion_student_response ?? null);
                    $completionStudentComment =
                        $updateData["completion_student_comment"] ??
                        ($booking->completion_student_comment ?? null);
                    $studPayload = [
                        "event" => "BookingUpdated",
                        "Booking_ID" => (int) $booking->Booking_ID,
                        "Status" => $status,
                        "Booking_Date" => $updateData["Booking_Date"] ?? $booking->Booking_Date,
                        "completion_reason" => $completionReason,
                        "completion_requested_at" => $completionRequestedAt,
                        "completion_reviewed_at" => $completionReviewedAt,
                        "completion_student_response" => $completionStudentResponse,
                        "completion_student_comment" => $completionStudentComment,
                    ];
                    event(new \App\Events\BookingUpdatedStudent($studId, $studPayload));
                }
            } catch (\Throwable $e) {
                // swallow broadcast errors
            }

            // Auto-reschedule opposite-mode pending bookings if this approval is the first for the day
            if ($status === "approved" && !empty($willBeFirstActive) && $willBeFirstActive) {
                try {
                    $allowedDays = $parseAllowedDays($booking->Prof_Schedule ?? null);
                    $currentDateKey = $booking->Booking_Date;
                    $profId = (int) $booking->Prof_ID;
                    $lockMode = strtolower((string) $booking->Mode);
                    // Find pending bookings on the same date with the opposite mode
                    $others = DB::table("t_consultation_bookings")
                        ->where("Prof_ID", $profId)
                        ->where("Booking_Date", $currentDateKey)
                        ->where("Status", "pending")
                        ->where("Booking_ID", "!=", $booking->Booking_ID)
                        ->where("Mode", "!=", $booking->Mode)
                        ->get();

                    foreach ($others as $ob) {
                        $target = $findNextAvailableDate(
                            $profId,
                            $currentDateKey,
                            (string) $ob->Mode,
                            $allowedDays,
                        );
                        if (!$target) {
                            continue; // no safe target found within 90 days
                        }
                        $reason =
                            "Auto-rescheduled: date locked to " .
                            ucfirst($lockMode) .
                            " mode by first approved booking.";
                        $ok = DB::table("t_consultation_bookings")
                            ->where("Booking_ID", $ob->Booking_ID)
                            ->update([
                                "Status" => "rescheduled",
                                "Booking_Date" => $target,
                                "reschedule_reason" => $reason,
                            ]);
                        if ($ok) {
                            // Update notifications for the affected booking
                            try {
                                Notification::updateNotificationStatus(
                                    (int) $ob->Booking_ID,
                                    "rescheduled",
                                    $professorName,
                                    $target,
                                    $reason,
                                );
                            } catch (\Throwable $e) {
                            }
                            // Broadcast to professor channel
                            try {
                                event(
                                    new \App\Events\BookingUpdated($profId, [
                                        "event" => "BookingUpdated",
                                        "Booking_ID" => (int) $ob->Booking_ID,
                                        "Status" => "rescheduled",
                                        "Booking_Date" => $target,
                                        "reschedule_reason" => $reason,
                                    ]),
                                );
                            } catch (\Throwable $e) {
                            }
                            // Broadcast to the student's channel
                            try {
                                $sId = (int) ($ob->Stud_ID ?? 0);
                                if ($sId > 0) {
                                    event(
                                        new \App\Events\BookingUpdatedStudent($sId, [
                                            "event" => "BookingUpdated",
                                            "Booking_ID" => (int) $ob->Booking_ID,
                                            "Status" => "rescheduled",
                                            "Booking_Date" => $target,
                                        ]),
                                    );
                                }
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Fail silently; main approval already succeeded
                }
            }
        }

        $readableStatus = ucwords(str_replace("_", " ", strtolower((string) $status)));

        return response()->json([
            "success" => $updated > 0,
            "message" => $updated
                ? "Status updated to {$readableStatus}."
                : "Failed to update status.",
        ]);
    } catch (\Exception $e) {
        return response()->json([
            "success" => false,
            "message" => "An error occurred: " . $e->getMessage(),
        ]);
    }
});

// Student cancel booking within 1 hour (pending only)
Route::post("/api/student/consultations/cancel", function (Request $request) {
    try {
        $user = Auth::user();
        if (!$user || !isset($user->Stud_ID)) {
            return response()->json(["success" => false, "message" => "Unauthorized"], 401);
        }
        $id = (int) $request->input("id");
        if (!$id) {
            return response()->json(
                ["success" => false, "message" => "Booking ID is required."],
                422,
            );
        }
        $booking = DB::table("t_consultation_bookings")->where("Booking_ID", $id)->first();
        if (!$booking) {
            return response()->json(["success" => false, "message" => "Booking not found."], 404);
        }
        if ((int) $booking->Stud_ID !== (int) $user->Stud_ID) {
            return response()->json(
                ["success" => false, "message" => "You can only cancel your own booking."],
                403,
            );
        }
        $status = strtolower((string) $booking->Status);
        if ($status !== "pending") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Only pending bookings can be cancelled by the student.",
                ],
                422,
            );
        }
        // Time window: within 1 hour from creation
        try {
            $created = \Carbon\Carbon::parse($booking->Created_At, "Asia/Manila");
        } catch (\Throwable $e) {
            $created = \Carbon\Carbon::now("Asia/Manila")->subYears(1);
        }
        $now = \Carbon\Carbon::now("Asia/Manila");
        if ($now->diffInSeconds($created) > 3600) {
            return response()->json(
                ["success" => false, "message" => "Cancellation window has expired (1 hour)."],
                422,
            );
        }

        $updated = DB::table("t_consultation_bookings")
            ->where("Booking_ID", $id)
            ->update(["Status" => "cancelled"]);
        if ($updated <= 0) {
            return response()->json(["success" => false, "message" => "Failed to cancel booking."]);
        }

        // Delete notifications for this booking for both professor and student
        try {
            \App\Models\Notification::where("booking_id", $id)->delete();
        } catch (\Throwable $e) {
        }

        // Broadcast to professor and student channels to update UI
        try {
            $profId = (int) $booking->Prof_ID;
            event(
                new \App\Events\BookingUpdated($profId, [
                    "event" => "BookingUpdated",
                    "Booking_ID" => (int) $booking->Booking_ID,
                    "Status" => "cancelled",
                ]),
            );
        } catch (\Throwable $e) {
        }
        try {
            $studId = (int) $booking->Stud_ID;
            if ($studId > 0) {
                event(
                    new \App\Events\BookingUpdatedStudent($studId, [
                        "event" => "BookingUpdated",
                        "Booking_ID" => (int) $booking->Booking_ID,
                        "Status" => "cancelled",
                    ]),
                );
            }
        } catch (\Throwable $e) {
        }

        return response()->json(["success" => true, "message" => "Booking cancelled."]);
    } catch (\Throwable $e) {
        return response()->json(
            ["success" => false, "message" => "Server error: " . $e->getMessage()],
            500,
        );
    }
})->middleware(["auth"]);

// Student: accept a rescheduled consultation (confirms the new date)
Route::post("/api/student/consultations/accept-reschedule", function (Request $request) {
    try {
        $user = Auth::user();
        if (!$user || !isset($user->Stud_ID)) {
            return response()->json(["success" => false, "message" => "Unauthorized"], 401);
        }
        $id = (int) $request->input("id");
        if (!$id) {
            return response()->json(
                ["success" => false, "message" => "Booking ID is required."],
                422,
            );
        }
        $booking = DB::table("t_consultation_bookings")->where("Booking_ID", $id)->first();
        if (!$booking) {
            return response()->json(["success" => false, "message" => "Booking not found."], 404);
        }
        if ((int) $booking->Stud_ID !== (int) $user->Stud_ID) {
            return response()->json(
                ["success" => false, "message" => "You can only act on your own booking."],
                403,
            );
        }
        $status = strtolower((string) $booking->Status);
        if ($status !== "rescheduled") {
            return response()->json(
                ["success" => false, "message" => "Only rescheduled bookings can be accepted."],
                422,
            );
        }
        // Accepting confirms the date; mark as approved
        $updated = DB::table("t_consultation_bookings")
            ->where("Booking_ID", $id)
            ->update(["Status" => "approved"]);
        if ($updated <= 0) {
            return response()->json([
                "success" => false,
                "message" => "Failed to accept reschedule.",
            ]);
        }
        // Notifications: specialized message for professor & student
        try {
            \App\Models\Notification::updateOnStudentAcceptReschedule(
                $id,
                (string) $booking->Booking_Date,
            );
        } catch (\Throwable $e) {
        }
        // Broadcast to professor and student
        try {
            event(
                new \App\Events\BookingUpdated((int) $booking->Prof_ID, [
                    "event" => "BookingUpdated",
                    "Booking_ID" => (int) $booking->Booking_ID,
                    "Status" => "approved",
                    "Booking_Date" => (string) $booking->Booking_Date,
                ]),
            );
        } catch (\Throwable $e) {
        }
        try {
            event(
                new \App\Events\BookingUpdatedStudent((int) $booking->Stud_ID, [
                    "event" => "BookingUpdated",
                    "Booking_ID" => (int) $booking->Booking_ID,
                    "Status" => "approved",
                    "Booking_Date" => (string) $booking->Booking_Date,
                ]),
            );
        } catch (\Throwable $e) {
        }

        return response()->json(["success" => true, "message" => "Reschedule accepted."]);
    } catch (\Throwable $e) {
        return response()->json(
            ["success" => false, "message" => "Server error: " . $e->getMessage()],
            500,
        );
    }
})->middleware(["auth"]);

// Student: cancel a rescheduled consultation (no 1-hour restriction)
Route::post("/api/student/consultations/cancel-rescheduled", function (Request $request) {
    try {
        $user = Auth::user();
        if (!$user || !isset($user->Stud_ID)) {
            return response()->json(["success" => false, "message" => "Unauthorized"], 401);
        }
        $id = (int) $request->input("id");
        if (!$id) {
            return response()->json(
                ["success" => false, "message" => "Booking ID is required."],
                422,
            );
        }
        $booking = DB::table("t_consultation_bookings")->where("Booking_ID", $id)->first();
        if (!$booking) {
            return response()->json(["success" => false, "message" => "Booking not found."], 404);
        }
        if ((int) $booking->Stud_ID !== (int) $user->Stud_ID) {
            return response()->json(
                ["success" => false, "message" => "You can only cancel your own booking."],
                403,
            );
        }
        $status = strtolower((string) $booking->Status);
        if ($status !== "rescheduled") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Only rescheduled bookings can be cancelled here.",
                ],
                422,
            );
        }
        $updated = DB::table("t_consultation_bookings")
            ->where("Booking_ID", $id)
            ->update(["Status" => "cancelled"]);
        if ($updated <= 0) {
            return response()->json(["success" => false, "message" => "Failed to cancel booking."]);
        }
        // Update notifications to cancelled for both sides
        try {
            \App\Models\Notification::updateNotificationStatus(
                $id,
                "cancelled",
                null,
                (string) $booking->Booking_Date,
            );
        } catch (\Throwable $e) {
        }
        // Broadcast to professor and student
        try {
            event(
                new \App\Events\BookingUpdated((int) $booking->Prof_ID, [
                    "event" => "BookingUpdated",
                    "Booking_ID" => (int) $booking->Booking_ID,
                    "Status" => "cancelled",
                ]),
            );
        } catch (\Throwable $e) {
        }
        try {
            event(
                new \App\Events\BookingUpdatedStudent((int) $booking->Stud_ID, [
                    "event" => "BookingUpdated",
                    "Booking_ID" => (int) $booking->Booking_ID,
                    "Status" => "cancelled",
                ]),
            );
        } catch (\Throwable $e) {
        }

        return response()->json(["success" => true, "message" => "Booking cancelled."]);
    } catch (\Throwable $e) {
        return response()->json(
            ["success" => false, "message" => "Server error: " . $e->getMessage()],
            500,
        );
    }
})->middleware(["auth"]);

// Student: single consultation details for modal (ownership checked)
Route::get("/api/student/consultation-details/{bookingId}", function ($bookingId) {
    try {
        $user = Auth::user();
        if (!$user || !isset($user->Stud_ID)) {
            return response()->json(["success" => false, "message" => "Unauthorized"], 401);
        }
        $row = DB::table("t_consultation_bookings as b")
            ->join("professors as p", "p.Prof_ID", "=", "b.Prof_ID")
            ->join("t_student as s", "s.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
            ->join("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->select([
                "b.Booking_ID as booking_id",
                "p.Name as professor_name",
                "subj.Subject_Name as subject",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as type"),
                "b.Booking_Date as booking_date",
                "b.Mode as mode",
                "b.reschedule_reason as reschedule_reason",
                "b.Status as status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
            ])
            ->where("b.Booking_ID", (int) $bookingId)
            ->where("b.Stud_ID", (int) $user->Stud_ID)
            ->first();
        if (!$row) {
            return response()->json(["success" => false, "message" => "Not found"], 404);
        }
        return response()->json(["success" => true, "consultation" => $row]);
    } catch (\Throwable $e) {
        return response()->json(
            ["success" => false, "message" => "Server error: " . $e->getMessage()],
            500,
        );
    }
})->middleware(["auth"]);

// Professor notifications (existing student routes above) - ensure professor guard usage
Route::get("/api/professor/notifications", [
    NotificationController::class,
    "getProfessorNotifications",
]);
Route::post("/api/professor/notifications/mark-read", [
    NotificationController::class,
    "markProfessorAsRead",
]);
Route::post("/api/professor/notifications/mark-all-read", [
    NotificationController::class,
    "markAllProfessorAsRead",
]);
Route::get("/api/professor/notifications/unread-count", [
    NotificationController::class,
    "getProfessorUnreadCount",
]);

Route::post("/professor/login-professor", [AuthControllerProfessor::class, "apiLogin"]);

// Unified messaging API routes secured for either authenticated student or professor
Route::middleware("auth:web,professor")->group(function () {
    Route::post("/send-message", [MessageController::class, "sendMessage"]);
    Route::post("/send-messageprof", [MessageController::class, "sendMessageprof"]);
    Route::get("/load-messages/{bookingId}", [MessageController::class, "loadMessages"]);
    // Direct messaging (booking independent)
    Route::get("/load-direct-messages/{studId}/{profId}", [
        MessageController::class,
        "loadDirectMessages",
    ])->name("messages.direct.load");
    // Chat utility endpoints
    Route::get("/chat/unread/student", [MessageController::class, "unreadCountsStudent"]);
    Route::get("/chat/unread/professor", [MessageController::class, "unreadCountsProfessor"]);
    Route::post("/chat/presence/ping", [MessageController::class, "presencePing"]);
    Route::get("/chat/presence/online", [MessageController::class, "onlineLists"]);
    Route::post("/chat/typing", [MessageController::class, "typing"]);
    // Minimal student summary for professor inbox realtime insert
    Route::get("/chat/student-summary/{studId}", [MessageController::class, "studentSummary"]);
    Route::post("/chat/read-pair", [MessageController::class, "markPairRead"]);
});

// Final student course list routes (protected)
// Student booking pages: protect by auth in normal runs, but allow public access during E2E tests
if (app()->environment("testing") || env("E2E_PUBLIC", false)) {
    Route::get("/comsci", [CardComsci::class, "showComsci"]);
} else {
    Route::get("/comsci", [CardComsci::class, "showComsci"])->middleware("auth");
}

Route::post("/send-file", [MessageController::class, "sendFile"])->middleware("auth:web,professor");

Route::post("/profile/upload-picture", [ProfileController::class, "uploadPicture"])->name(
    "profile.uploadPicture",
);
Route::post("/profile/delete-picture", [ProfileController::class, "deletePicture"])->name(
    "profile.deletePicture",
);

Route::post("/profile/upload-pictureprof", [
    ProfessorProfileController::class,
    "uploadPicture",
])->name("profile.uploadPicture.professor");
Route::post("/profile/delete-pictureprof", [
    ProfessorProfileController::class,
    "deletePicture",
])->name("profile.deletePicture.professor");

Route::post("/admin/logout", [AdminAuthController::class, "logout"])->name("logout.admin");

// Example admin dashboard route (create this view/controller as needed)

// Admin analytics page & data
Route::get("/admin-analytics", [AdminAnalyticsController::class, "index"])
    ->name("admin.analytics")
    ->middleware([\App\Http\Middleware\EnsureAdminAuthenticated::class]);
Route::get("/api/admin/analytics", [AdminAnalyticsController::class, "data"])
    ->name("admin.analytics.data")
    ->middleware([\App\Http\Middleware\EnsureAdminAuthenticated::class]);

Route::get("/admin-comsci", [ConsultationBookingController::class, "showFormAdmin"])
    ->name("admin.comsci")
    ->middleware([\App\Http\Middleware\EnsureAdminAuthenticated::class]);
Route::post("/admin-comsci/add-professor", [ConsultationBookingController::class, "addProfessor"])
    ->name("admin.comsci.professor.add")
    ->middleware("auth:admin");
Route::post("/admin-comsci/add-student", [ConsultationBookingController::class, "addStudent"])
    ->name("admin.comsci.student.add")
    ->middleware(["auth:admin", "throttle:5,1"]);

Route::middleware([\App\Http\Middleware\EnsureAdminAuthenticated::class])->group(function () {
    Route::get("/admin/subjects", [
        \App\Http\Controllers\AdminSubjectController::class,
        "index",
    ])->name("admin.subjects.index");
    Route::delete("/admin/subjects/{subject}", [
        \App\Http\Controllers\AdminSubjectController::class,
        "destroy",
    ])->name("admin.subjects.destroy");
    Route::get("/admin/academic-terms", [AdminAcademicTermController::class, "index"])->name(
        "terms.index",
    );
    Route::post("/admin/academic-years", [AdminAcademicTermController::class, "store"])->name(
        "terms.store",
    );
    Route::post("/admin/academic-years/{academicYear}/terms", [
        AdminAcademicTermController::class,
        "storeTerm",
    ])->name("terms.store-term");
    Route::put("/admin/terms/{term}", [AdminAcademicTermController::class, "update"])->name(
        "terms.update",
    );
    Route::post("/admin/terms/{term}/activate", [
        AdminAcademicTermController::class,
        "activate",
    ])->name("terms.activate");
});

Route::post("/admin-comsci/assign-subjects", [
    ConsultationBookingController::class,
    "assignSubjects",
])->name("admin.professor.assignSubjects");

Route::post("/admin-comsci/edit-professor/{profId}", [
    ConsultationBookingController::class,
    "editProfessor",
])->name("admin.comsci.professor.edit");
Route::post("/admin-comsci/update-professor/{profId}", [
    ConsultationBookingController::class,
    "updateProfessor",
])
    ->name("admin.comsci.professor.update")
    ->middleware(["auth:admin", "throttle:5,1"]);
Route::get("/admin-comsci/professor-subjects/{profId}", [
    ConsultationBookingController::class,
    "getProfessorSubjects",
])->name("admin.comsci.professor.subjects");

// (duplicate removed; ITIS delete route defined above with throttle)

Route::delete("/admin-comsci/delete-professor/{prof}", [
    ConsultationBookingController::class,
    "deleteProfessor",
])
    ->name("admin.comsci.professor.delete")
    ->middleware(["auth:admin", "throttle:3,1"]);

Route::get("/notifications", [NotificationController::class, "index"])->name("notifications.index");
Route::get("/notifications/{id}", [NotificationController::class, "show"])->name(
    "notifications.show",
);
Route::post("/notifications", [NotificationController::class, "store"])->name(
    "notifications.store",
);
Route::put("/notifications/{id}", [NotificationController::class, "update"])->name(
    "notifications.update",
);
Route::delete("/notifications/{id}", [NotificationController::class, "destroy"])->name(
    "notifications.destroy",
);

// Notification routes
Route::get("/api/notifications", [NotificationController::class, "getNotifications"])->name(
    "notifications.get",
);
Route::post("/api/notifications/mark-read", [NotificationController::class, "markAsRead"])->name(
    "notifications.mark-read",
);
Route::post("/api/notifications/mark-all-read", [
    NotificationController::class,
    "markAllAsRead",
])->name("notifications.mark-all-read");
Route::get("/api/notifications/unread-count", [
    NotificationController::class,
    "getUnreadCount",
])->name("notifications.unread-count");

// Professor notification routes
Route::get("/api/professor/notifications", [
    NotificationController::class,
    "getProfessorNotifications",
])->name("professor.notifications.get");
Route::post("/api/professor/notifications/mark-read", [
    NotificationController::class,
    "markProfessorAsRead",
])->name("professor.notifications.mark-read");
Route::post("/api/professor/notifications/mark-all-read", [
    NotificationController::class,
    "markAllProfessorAsRead",
])->name("professor.notifications.mark-all-read");
Route::get("/api/professor/notifications/unread-count", [
    NotificationController::class,
    "getProfessorUnreadCount",
])->name("professor.notifications.unread-count");

// Admin API routes
Route::get("/api/admin/all-consultations", [
    ConsultationLogController::class,
    "getAllConsultations",
])->name("admin.consultations.all");
Route::get("/api/admin/consultation-details/{bookingId}", [
    ConsultationLogController::class,
    "getConsultationDetails",
])->name("admin.consultation.details");
Route::get("/api/admin/notifications", [
    NotificationController::class,
    "getAdminNotifications",
])->name("admin.notifications.get");
Route::post("/api/admin/notifications/mark-read", [
    NotificationController::class,
    "markAdminAsRead",
])->name("admin.notifications.mark-read");
Route::post("/api/admin/notifications/mark-all-read", [
    NotificationController::class,
    "markAllAdminAsRead",
])->name("admin.notifications.mark-all-read");
Route::get("/api/admin/notifications/unread-count", [
    NotificationController::class,
    "getAdminUnreadCount",
])->name("admin.notifications.unread-count");

// Admin calendar override routes
Route::middleware([\App\Http\Middleware\EnsureAdminAuthenticated::class])->group(function () {
    Route::post("/api/admin/calendar/overrides/preview", [
        \App\Http\Controllers\AdminCalendarOverrideController::class,
        "preview",
    ]);
    Route::post("/api/admin/calendar/overrides/apply", [
        \App\Http\Controllers\AdminCalendarOverrideController::class,
        "apply",
    ]);
    Route::get("/api/admin/calendar/overrides", [
        \App\Http\Controllers\AdminCalendarOverrideController::class,
        "list",
    ]);
    Route::post("/api/admin/calendar/overrides/remove", [
        \App\Http\Controllers\AdminCalendarOverrideController::class,
        "remove",
    ]);
});

// Public/student-facing override list (global only)
Route::get("/api/calendar/overrides", [
    \App\Http\Controllers\AdminCalendarOverrideController::class,
    "publicList",
]);

// Public/student-facing override list merged with a specific professor's overrides
Route::get("/api/calendar/overrides/professor", [
    \App\Http\Controllers\AdminCalendarOverrideController::class,
    "publicProfessorList",
]);

// Professor-facing override list (global + professor scope)
Route::middleware([\App\Http\Middleware\EnsureProfessorAuthenticated::class])->group(function () {
    Route::get("/api/professor/calendar/overrides", [
        \App\Http\Controllers\AdminCalendarOverrideController::class,
        "professorList",
    ]);
    // Professor leave day apply/remove
    Route::post("/api/professor/calendar/leave/apply", [
        \App\Http\Controllers\ProfessorCalendarOverrideController::class,
        "applyLeave",
    ]);
    Route::post("/api/professor/calendar/leave/remove", [
        \App\Http\Controllers\ProfessorCalendarOverrideController::class,
        "removeLeave",
    ]);
});

// Debug route for notifications
Route::get("/debug/notifications", function () {
    $notifications = DB::table("notifications")
        ->join("professors", "notifications.user_id", "=", "professors.Prof_ID")
        ->select("notifications.*", "professors.Name as professor_name")
        ->orderBy("notifications.created_at", "desc")
        ->limit(10)
        ->get();

    $bookings = DB::table("t_consultation_bookings")
        ->join("t_student", "t_consultation_bookings.Stud_ID", "=", "t_student.Stud_ID")
        ->join("professors", "t_consultation_bookings.Prof_ID", "=", "professors.Prof_ID")
        ->select(
            "t_consultation_bookings.*",
            "t_student.Name as student_name",
            "professors.Name as professor_name",
        )
        ->orderBy("t_consultation_bookings.Created_At", "desc")
        ->limit(10)
        ->get();

    return view("debug.notifications", [
        "notifications" => $notifications,
        "bookings" => $bookings,
    ]);
});

Route::get("/admin/dashboard", AdminDashboardController::class)
    ->name("admin.dashboard")
    ->middleware([
        \App\Http\Middleware\EnsureAdminAuthenticated::class,
        \App\Http\Middleware\PreventBackHistory::class,
    ]);
