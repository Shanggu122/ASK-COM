<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Models\Professor;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Events\ProfessorAdded;
use App\Events\ProfessorDeleted;
use App\Events\ProfessorUpdated;
use App\Services\CalendarOverrideService;
use App\Mail\ProfessorWelcomeMail;
use App\Mail\StudentWelcomeMail;
use App\Models\User;

class ConsultationBookingController extends Controller
{
    /**
     * Store a new student consultation booking.
     */
    public function store(Request $request)
    {
        // 1) Validate incoming data in two steps so we can relax rules for "General Consultation"
        $base = $request->validate(
            [
                "subject_id" => "required|integer|exists:t_subject,Subject_ID",
                "booking_date" => "required|string|max:50",
                "mode" => "required|in:online,onsite",
                "prof_id" => "required|integer|exists:professors,Prof_ID",
                // Types are optional here; we will enforce them conditionally below
                "types" => "sometimes|array",
                "types.*" => "integer|exists:t_consultation_types,Consult_type_ID|string|max:255",
                "other_type_text" => "nullable|string|max:255",
            ],
            [
                "subject_id.required" => "Please select a subject.",
                "booking_date.required" => "Please select a booking date.",
                "mode.required" => "Please select consultation mode (Online or Onsite).",
                "prof_id.required" => "Professor information is missing. Please try again.",
            ],
        );

        // Determine if subject is the special "General Consultation"
        $subjectRow = DB::table("t_subject")->where("Subject_ID", $base["subject_id"])->first();
        $isGeneralSubject = false;
        if ($subjectRow && isset($subjectRow->Subject_Name)) {
            $isGeneralSubject =
                strcasecmp(trim($subjectRow->Subject_Name), "General Consultation") === 0;
        }

        // If not General Consultation, require at least one consultation type
        if (!$isGeneralSubject) {
            if (
                !$request->has("types") ||
                empty($request->input("types")) ||
                !is_array($request->input("types"))
            ) {
                return $request->wantsJson()
                    ? response()->json(
                        [
                            "success" => false,
                            "message" => "Please select at least one consultation type.",
                            "errors" => [
                                "types" => ["Please select at least one consultation type."],
                            ],
                        ],
                        422,
                    )
                    : redirect()
                        ->back()
                        ->withErrors(["types" => "Please select at least one consultation type."])
                        ->withInput();
            }
        }
        // Merge back for existing variable name compatibility
        $data = $base;
        $data["types"] = $request->input("types", []);

        // Normalize to Asia/Manila and enforce weekday (Mon-Fri) only
        $rawInputDate = $data["booking_date"] ?? null;
        $carbonDate = null;
        if ($rawInputDate) {
            // Remove commas; try specific formats then fallback
            $clean = str_replace(",", "", trim($rawInputDate)); // e.g. "Fri Aug 29 2025"
            $tryFormats = ["D M d Y", "D M d Y H:i", "Y-m-d"];
            foreach ($tryFormats as $fmt) {
                try {
                    $carbonDate = Carbon::createFromFormat($fmt, $clean, "Asia/Manila");
                    break;
                } catch (\Exception $e) {
                }
            }
            if (!$carbonDate) {
                // fallback generic
                try {
                    $carbonDate = Carbon::parse($clean, "Asia/Manila");
                } catch (\Exception $e) {
                }
            }
        }
        if (!$carbonDate) {
            $carbonDate = Carbon::now("Asia/Manila");
        }
        $carbonDate = $carbonDate->setTimezone("Asia/Manila")->startOfDay();
        if ($carbonDate->isWeekend()) {
            $msg = "Weekend dates (Sat/Sun) are not allowed. Please pick a weekday (Mon–Fri).";
            if ($request->wantsJson()) {
                return response()->json(
                    ["success" => false, "message" => $msg, "errors" => ["booking_date" => [$msg]]],
                    422,
                );
            }
            return redirect()
                ->back()
                ->withErrors(["booking_date" => $msg])
                ->withInput();
        }
        // Enforce booking window: allow only current month; open next month when today is within last week's Monday onwards
        $today = Carbon::now("Asia/Manila")->startOfDay();
        $curStart = $today->copy()->startOfMonth();
        $curEnd = $today->copy()->endOfMonth();
        // Compute Monday of the last week (week that contains the month's last day, Monday-start)
        $lastDay = $curEnd->copy();
        // Carbon weekday: 0=Sun..6=Sat; we want Monday(1)
        $dow = (int) $lastDay->dayOfWeek; // 0..6
        $diff = ($dow - 1 + 7) % 7; // days back to Monday
        $lastWeekMon = $lastDay->copy()->subDays($diff)->startOfDay();
        $maxAllowed = $curEnd->copy();
        if ($today->greaterThanOrEqualTo($lastWeekMon)) {
            // Extend to end of next month
            $maxAllowed = $today->copy()->addMonth()->endOfMonth();
        }
        if ($carbonDate->lt($today) || $carbonDate->gt($maxAllowed)) {
            $msg =
                "You can only book within the current month. The next month opens during the last week of this month.";
            if ($request->wantsJson()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => $msg,
                        "errors" => ["booking_date" => [$msg]],
                    ],
                    422,
                );
            }
            return redirect()
                ->back()
                ->withErrors(["booking_date" => $msg])
                ->withInput();
        }
        $date = $carbonDate->format("D M d Y");

        // Prevent duplicate same-day bookings per student until the prior one is resolved
        $studentId = Auth::user()->Stud_ID ?? null;
        if ($studentId) {
            $blockingStatuses = [
                "pending",
                "approved",
                "completion_pending",
                "completion_declined",
            ];
            $hasExisting = DB::table("t_consultation_bookings")
                ->where("Stud_ID", $studentId)
                ->where("Booking_Date", $date)
                ->whereIn("Status", $blockingStatuses)
                ->exists();
            if ($hasExisting) {
                $msg =
                    "You already have a consultation booked for this date. Please finish, cancel, or reschedule the existing request before creating another.";
                if ($request->wantsJson()) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => $msg,
                            "errors" => ["booking_date" => [$msg]],
                        ],
                        422,
                    );
                }
                return redirect()
                    ->back()
                    ->withErrors(["booking_date" => $msg])
                    ->withInput();
            }
        }

        // Check if "Others" is selected (Consult_type_ID = 6 in your DB) – only when not General Consultation
        $customType = null;
        if ($isGeneralSubject) {
            $customType = trim((string) ($data["other_type_text"] ?? ""));
            if ($customType === "") {
                $msg = "Please specify the consultation details for General Consultation.";
                if ($request->wantsJson()) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => $msg,
                            "errors" => ["other_type_text" => [$msg]],
                        ],
                        422,
                    );
                }
                return redirect()
                    ->back()
                    ->withErrors(["other_type_text" => $msg])
                    ->withInput();
            }
        } else {
            if (in_array(6, $data["types"])) {
                $customType = trim((string) ($data["other_type_text"] ?? ""));
                if ($customType === "") {
                    $msg = "Please specify the consultation type in the Others field.";
                    if ($request->wantsJson()) {
                        return response()->json(
                            [
                                "success" => false,
                                "message" => $msg,
                                "errors" => ["other_type_text" => [$msg]],
                            ],
                            422,
                        );
                    }
                    return redirect()
                        ->back()
                        ->withErrors(["other_type_text" => $msg])
                        ->withInput();
                }
            }
        }

        // Override checks first: block/forced_mode
        $override = app(CalendarOverrideService::class)->evaluate((int) $data["prof_id"], $date);
        if ($override["blocked"] ?? false) {
            $msg = "Selected date is not available for consultations.";
            return $request->wantsJson()
                ? response()->json(["success" => false, "message" => $msg], 422)
                : redirect()
                    ->back()
                    ->withErrors(["booking_date" => $msg])
                    ->withInput();
        }
        if (!empty($override["forced_mode"]) && $override["forced_mode"] !== $data["mode"]) {
            $msg =
                "This date is restricted to " .
                ucfirst($override["forced_mode"]) .
                " consultations.";
            return $request->wantsJson()
                ? response()->json(["success" => false, "message" => $msg], 422)
                : redirect()
                    ->back()
                    ->withErrors(["mode" => $msg])
                    ->withInput();
        }

        // Capacity check: limit 5 already approved/rescheduled bookings per professor per date.
        // Pending bookings do NOT count yet; capacity enforced again when approving.
        $capacityStatuses = ["approved", "rescheduled"];
        $existingFilled = DB::table("t_consultation_bookings")
            ->where("Prof_ID", $data["prof_id"])
            ->where("Booking_Date", $date)
            ->whereIn("Status", $capacityStatuses)
            ->count();
        if ($existingFilled >= 5) {
            $msg =
                "Selected date already has 5 approved/rescheduled bookings for this professor. Please choose another date.";
            if ($request->wantsJson()) {
                return response()->json(
                    ["success" => false, "message" => $msg, "errors" => ["booking_date" => [$msg]]],
                    422,
                );
            }
            return redirect()
                ->back()
                ->withErrors(["booking_date" => $msg])
                ->withInput();
        }

        // Mode-lock rule: if any approved/rescheduled booking exists for this prof/date,
        // the day's consultation mode is locked by the earliest such booking.
        $lockStatuses = ["approved", "rescheduled"];
        $firstExisting = DB::table("t_consultation_bookings")
            ->where("Prof_ID", $data["prof_id"])
            ->where("Booking_Date", $date)
            ->whereIn("Status", $lockStatuses)
            ->orderBy("Booking_ID", "asc")
            ->first();
        if (
            $firstExisting &&
            isset($firstExisting->Mode) &&
            $firstExisting->Mode !== $data["mode"]
        ) {
            $msg =
                "Consultation mode for this date is locked to " .
                ucfirst($firstExisting->Mode) .
                ". Please select another date.";
            if ($request->wantsJson()) {
                return response()->json(
                    ["success" => false, "message" => $msg, "errors" => ["mode" => [$msg]]],
                    422,
                );
            }
            return redirect()
                ->back()
                ->withErrors(["mode" => $msg])
                ->withInput();
        }

        // Insert into t_consultation_bookings
        try {
            $bookingId = DB::table("t_consultation_bookings")->insertGetId([
                "Stud_ID" => $studentId,
                "Prof_ID" => $data["prof_id"],
                // For General Consultation, no specific consultation type is stored
                "Consult_type_ID" => $isGeneralSubject ? null : $data["types"][0] ?? null,
                "Custom_Type" => $customType,
                "Subject_ID" => $data["subject_id"],
                "Booking_Date" => $date,
                "Mode" => $data["mode"],
                "Status" => "pending",
                "Created_At" => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Consultation booking insert failed", [
                "student_id" => $studentId,
                "prof_id" => $data["prof_id"],
                "subject_id" => $data["subject_id"],
                "error" => $e->getMessage(),
            ]);

            $failMsg =
                "We could not save your booking right now. Please try again or contact the support team.";

            if ($request->wantsJson()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => $failMsg,
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->withErrors(["booking" => $failMsg])
                ->withInput();
        }

        // Create notification for the professor
        if ($bookingId) {
            try {
                $student = Auth::user();
                $professor = DB::table("professors")->where("Prof_ID", $data["prof_id"])->first();
                $subject = DB::table("t_subject")
                    ->where("Subject_ID", $data["subject_id"])
                    ->first();
                $consultationType = null;
                if (!$isGeneralSubject && !empty($data["types"])) {
                    $consultationType = DB::table("t_consultation_types")
                        ->where("Consult_type_ID", $data["types"][0])
                        ->first();
                }

                $studentName = $student->Name ?? "A student";
                $subjectName = $subject->Subject_Name ?? "Unknown subject";
                $typeName = $consultationType->Consult_Type ?? "consultation";
                if ($isGeneralSubject) {
                    $typeName = $customType ?: "General Consultation";
                } elseif ($customType) {
                    $typeName = $customType;
                }

                Notification::createProfessorNotification(
                    $data["prof_id"],
                    $bookingId,
                    $studentName,
                    $subjectName,
                    $date,
                    $typeName,
                );
            } catch (\Exception $e) {
                Log::error("Failed to create notification: " . $e->getMessage());
                // Continue with the booking even if notification fails
            }
        }

        // Broadcast new pending booking to the professor's booking channel for realtime insert
        try {
            if (isset($bookingId) && $bookingId) {
                $profId = (int) $data["prof_id"];
                $student = Auth::user();
                $subject = DB::table("t_subject")
                    ->where("Subject_ID", $data["subject_id"])
                    ->first();
                $consultationType = null;
                if (!$isGeneralSubject && !empty($data["types"])) {
                    $consultationType = DB::table("t_consultation_types")
                        ->where("Consult_type_ID", $data["types"][0])
                        ->first();
                }
                $typeName = $consultationType->Consult_Type ?? "consultation";
                if ($isGeneralSubject) {
                    $typeName = $customType ?: "General Consultation";
                } elseif (!empty($customType)) {
                    $typeName = $customType;
                }
                // Professor channel
                event(
                    new \App\Events\BookingUpdated($profId, [
                        "event" => "BookingCreated",
                        "Booking_ID" => (int) $bookingId,
                        "student_id" => (int) ($student->Stud_ID ?? 0),
                        "student" => $student->Name ?? "A student",
                        "subject" => $subject->Subject_Name ?? "Unknown subject",
                        "type" => $typeName,
                        "Booking_Date" => $date,
                        "Mode" => $data["mode"],
                        // Include creation timestamp so the professor log can render the First badge immediately
                        "Created_At" => now("Asia/Manila")->toIso8601String(),
                        "Status" => "pending",
                    ]),
                );

                // Student channel
                $studId = (int) ($student->Stud_ID ?? 0);
                if ($studId > 0) {
                    event(
                        new \App\Events\BookingUpdatedStudent($studId, [
                            "event" => "BookingCreated",
                            "Booking_ID" => (int) $bookingId,
                            "Professor" =>
                                DB::table("professors")->where("Prof_ID", $profId)->value("Name") ??
                                "Professor",
                            "subject" => $subject->Subject_Name ?? "Unknown subject",
                            "type" => $typeName,
                            "Booking_Date" => $date,
                            "Mode" => $data["mode"],
                            "Created_At" => now()->toDateTimeString(),
                            "Status" => "pending",
                        ]),
                    );
                }
            }
        } catch (\Throwable $e) {
            // ignore broadcast failure
        }

        // 4) Respond success
        if ($request->wantsJson()) {
            return response()->json([
                "success" => true,
                "message" =>
                    "Consultation booking submitted successfully! You will be notified once the professor responds.",
            ]);
        }
        return redirect()
            ->back()
            ->with(
                "success",
                "Consultation booking submitted successfully! You will be notified once the professor responds.",
            );
    }

    /**
     * Add a new student (compact flow from admin ITIS/ComSci pages).
     * Accepts: Stud_ID (<=9 numeric), Name (<=50), Email (<=100), Dept_ID (IT/CS or 1/2), Password (min:6)
     * Returns JSON: { success: bool, student?: object, message?: string, errors?: object }
     */
    public function addStudent(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    "Stud_ID" => "required|string|max:9|unique:t_student,Stud_ID",
                    "Name" => "required|string|max:50",
                    "Email" => "required|email|max:100|unique:t_student,Email",
                    "Dept_ID" => "required",
                    "Password" => "required|string|min:6",
                ],
                [
                    "Stud_ID.unique" => "Student ID already exists.",
                    "Email.unique" => "Email is already registered to another student.",
                ],
            );

            // Normalize Dept_ID to numeric codes used by DB: 1 (IT/ITIS), 2 (CS/ComSci)
            $deptRaw = $validated["Dept_ID"];
            if (is_numeric($deptRaw)) {
                $dept = (int) $deptRaw;
            } else {
                $upper = strtoupper((string) $deptRaw);
                if ($upper === "IT" || $upper === "ITIS" || $upper === "1") {
                    $dept = 1;
                } elseif ($upper === "CS" || $upper === "COMSCI" || $upper === "2") {
                    $dept = 2;
                } else {
                    // Fallback: try integer cast; default to 1 (IT) if invalid
                    $dept = is_numeric($deptRaw) ? (int) $deptRaw : 1;
                }
            }

            $plainPassword = $validated["Password"];

            // Create the student (t_student) using the existing User model mapping
            $student = User::create([
                "Stud_ID" => $validated["Stud_ID"],
                "Name" => $validated["Name"],
                "Dept_ID" => $dept,
                "Email" => $validated["Email"],
                // Match current login behavior: passwords may be plain or hashed; we'll hash for new records
                "Password" => Hash::make($plainPassword),
                "profile_picture" => null,
            ]);

            // Prepare minimal payload for UI
            $payload = [
                "Stud_ID" => $student->Stud_ID,
                "Name" => $student->Name,
                "Dept_ID" => (int) $student->Dept_ID,
                "Email" => $student->Email,
                "profile_picture" => $student->profile_picture,
                "profile_photo_url" => $student->profile_photo_url,
            ];

            // Send welcome email to student
            try {
                $loginUrl = route("login");
                Mail::to($student->Email)->send(
                    new StudentWelcomeMail(
                        $student->Name,
                        $student->Email,
                        $plainPassword,
                        $loginUrl,
                    ),
                );
            } catch (\Throwable $e) {
                Log::warning("Student welcome email failed", [
                    "stud_id" => $student->Stud_ID,
                    "err" => $e->getMessage(),
                ]);
            }

            return response()->json(["success" => true, "student" => $payload]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Validation failed",
                    "errors" => $ve->errors(),
                ],
                422,
            );
        } catch (\Throwable $e) {
            Log::error("Add student failed", ["error" => $e->getMessage()]);
            return response()->json(
                ["success" => false, "message" => "Failed to add student"],
                500,
            );
        }
    }

    public function showForm()
    {
        $professors = \App\Models\Professor::with("subjects")->where("Dept_ID", 2)->get();
        $consultationTypes = DB::table("t_consultation_types")->get();

        return view("comsci", [
            "professors" => $professors,
            "consultationTypes" => $consultationTypes,
        ]);
    }

    public function showFormAdmin()
    {
        $professors = \App\Models\Professor::where("Dept_ID", 2)->get();
        $subjects = \App\Models\Subject::all();
        return view("admin-comsci", [
            "professors" => $professors,
            "subjects" => $subjects,
        ]);
    }

    /**
     * Return subject ids currently assigned to a professor (AJAX JSON).
     */
    public function getProfessorSubjects($profId)
    {
        try {
            $professor = Professor::with("subjects")->find($profId);
            if (!$professor) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Professor not found",
                    ],
                    404,
                );
            }
            return response()->json([
                "success" => true,
                "subjects" => $professor->subjects->pluck("Subject_ID"),
            ]);
        } catch (\Exception $e) {
            Log::error("getProfessorSubjects error: " . $e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Server error retrieving subjects",
                ],
                500,
            );
        }
    }

    /**
     * Assign subjects to a professor (used by legacy route). Accepts prof_id & subjects[]
     */
    public function assignSubjects(Request $request)
    {
        $data = $request->validate([
            "prof_id" => "required|integer|exists:professors,Prof_ID",
            "subjects" => "array",
            "subjects.*" => "integer|exists:t_subject,Subject_ID",
        ]);
        try {
            $professor = Professor::find($data["prof_id"]);
            $professor->subjects()->sync($data["subjects"] ?? []);
            return response()->json(["success" => true]);
        } catch (\Exception $e) {
            Log::error("assignSubjects error: " . $e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Failed to assign subjects",
                ],
                500,
            );
        }
    }

    /**
     * Update professor (name, schedule, subject assignments) from admin panel.
     */
    public function updateProfessor(Request $request, $profId)
    {
        try {
            $professor = Professor::find($profId);
            if (!$professor) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Professor not found",
                    ],
                    404,
                );
            }

            // Department guard: ensure ComSci route only updates ComSci (Dept_ID=2).
            // We detect by the current request path.
            $path = $request->path(); // e.g., "admin-comsci/update-professor/123"
            $requiredDept = null;
            if (str_starts_with($path, "admin-comsci/")) {
                $requiredDept = 2;
            }
            if ($requiredDept !== null && (int) ($professor->Dept_ID ?? 0) !== $requiredDept) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Forbidden: department mismatch",
                    ],
                    403,
                );
            }

            // Validate basic fields. Prof_ID field in form is ignored for update of PK.
            $validated = $request->validate([
                "Name" => "required|string|max:50",
                "Schedule" => "nullable|string",
                "subjects" => "array",
                "subjects.*" => "integer|exists:t_subject,Subject_ID",
            ]);

            // Determine if anything actually changed
            $newName = $validated["Name"];
            $newSchedule = array_key_exists("Schedule", $validated)
                ? $validated["Schedule"] ?? null
                : null;

            $hasAttrChange = false;
            if ($professor->Name !== $newName) {
                $hasAttrChange = true;
            }
            if (array_key_exists("Schedule", $validated)) {
                $curSched = (string) ($professor->Schedule ?? "");
                $newSched = (string) ($newSchedule ?? "");
                if ($curSched !== $newSched) {
                    $hasAttrChange = true;
                }
            }

            $hasSubjectsChange = false;
            if ($request->has("subjects")) {
                $newSubjects = collect($validated["subjects"] ?? [])
                    ->map(fn($v) => (int) $v)
                    ->unique()
                    ->sort()
                    ->values();
                $currentSubjects = $professor
                    ->subjects()
                    ->pluck("t_subject.Subject_ID")
                    ->map(fn($v) => (int) $v)
                    ->unique()
                    ->sort()
                    ->values();
                $hasSubjectsChange = $newSubjects->toJson() !== $currentSubjects->toJson();
            }

            if (!$hasAttrChange && !$hasSubjectsChange) {
                return response()->json([
                    "success" => false,
                    "message" => "No changes detected",
                ]);
            }

            // Apply changes
            if ($hasAttrChange) {
                $professor->Name = $newName;
                if (array_key_exists("Schedule", $validated)) {
                    $professor->Schedule = $newSchedule;
                }
                $professor->save();
            }

            if ($hasSubjectsChange) {
                $professor->subjects()->sync($validated["subjects"] ?? []);
            }

            // Broadcast update (only if anything changed)
            event(new ProfessorUpdated($professor));

            return response()->json([
                "success" => true,
                "message" => "Professor updated",
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Validation error",
                    "errors" => $ve->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error("updateProfessor error: " . $e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Server error updating professor",
                ],
                500,
            );
        }
    }

    /**
     * Create a new professor (admin panel form submit).
     */
    public function addProfessor(Request $request)
    {
        try {
            // Only Faculty ID must be unique. Allow duplicate Names (and Emails if business rules permit duplicates).
            // If you still want Email unique, add |unique:professors,Email back to the rule below.
            $validated = $request->validate(
                [
                    "Prof_ID" => "required|integer|unique:professors,Prof_ID",
                    "Name" => "required|string|max:50",
                    "Email" => "required|email|max:100",
                    "Dept_ID" => "required|integer",
                    "Password" => "required|string|min:6",
                    "Schedule" => "nullable|string",
                    "subjects" => "array",
                    "subjects.*" => "integer|exists:t_subject,Subject_ID",
                ],
                [
                    "Prof_ID.unique" => "Faculty ID already exists.",
                ],
            );

            $professor = Professor::create([
                "Prof_ID" => $validated["Prof_ID"],
                "Name" => $validated["Name"],
                "Email" => $validated["Email"],
                "Dept_ID" => $validated["Dept_ID"],
                "Password" => Hash::make($validated["Password"]),
                "Schedule" => $validated["Schedule"] ?? null,
            ]);

            if ($request->has("subjects")) {
                $professor->subjects()->sync($validated["subjects"] ?? []);
            }

            // Broadcast new professor
            event(new ProfessorAdded($professor));

            // Send welcome email with temporary password
            try {
                $loginUrl = route("login");
                Mail::to($validated["Email"])->send(
                    new ProfessorWelcomeMail(
                        $validated["Name"],
                        $validated["Email"],
                        $validated["Password"], // pass plain temp password
                        $loginUrl,
                    ),
                );
            } catch (\Throwable $mailEx) {
                Log::error("Professor welcome email failed: " . $mailEx->getMessage());
            }

            if ($request->wantsJson()) {
                return response()->json([
                    "success" => true,
                    "message" => "Professor added",
                    "professor" => $professor,
                ]);
            }
            return redirect()->back()->with("success", "Professor added successfully");
        } catch (\Illuminate\Validation\ValidationException $ve) {
            if ($request->wantsJson()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Validation error",
                        "errors" => $ve->errors(),
                    ],
                    422,
                );
            }
            return redirect()->back()->withErrors($ve->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("addProfessor error: " . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Server error adding professor",
                    ],
                    500,
                );
            }
            return redirect()
                ->back()
                ->withErrors(["general" => "Server error adding professor"])
                ->withInput();
        }
    }

    /**
     * Return professor details (optional route usage) as JSON for editing.
     */
    public function editProfessor($profId)
    {
        try {
            $professor = Professor::with("subjects")->find($profId);
            if (!$professor) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Professor not found",
                    ],
                    404,
                );
            }
            return response()->json([
                "success" => true,
                "professor" => $professor,
                "subjects" => $professor->subjects->pluck("Subject_ID"),
            ]);
        } catch (\Exception $e) {
            Log::error("editProfessor fetch error: " . $e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Server error retrieving professor",
                ],
                500,
            );
        }
    }

    /**
     * Delete professor (and cascades remove pivot records).
     */
    public function deleteProfessor($profId)
    {
        try {
            $professor = Professor::find($profId);
            if (!$professor) {
                return request()->wantsJson()
                    ? response()->json(
                        ["success" => false, "message" => "Professor not found"],
                        404,
                    )
                    : redirect()
                        ->back()
                        ->withErrors(["general" => "Professor not found"]);
            }
            $deptId = $professor->Dept_ID;
            $profId = $professor->Prof_ID;
            $professor->delete();
            // Broadcast deletion
            event(new ProfessorDeleted($profId, $deptId));
            if (request()->wantsJson()) {
                return response()->json(["success" => true, "message" => "Professor deleted"]);
            }
            return redirect()->back()->with("success", "Professor deleted successfully");
        } catch (\Exception $e) {
            Log::error("deleteProfessor error: " . $e->getMessage());
            if (request()->wantsJson()) {
                return response()->json(
                    ["success" => false, "message" => "Server error deleting professor"],
                    500,
                );
            }
            return redirect()
                ->back()
                ->withErrors(["general" => "Server error deleting professor"]);
        }
    }
}
