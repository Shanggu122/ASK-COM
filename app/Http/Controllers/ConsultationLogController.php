<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // Add this

class ConsultationLogController extends Controller
{
    // app/Http/Controllers/ConsultationLogController.php
    public function index()
    {
        $user = Auth::user();

        $query = DB::table("t_consultation_bookings as b")
            ->join("professors as p", "p.Prof_ID", "=", "b.Prof_ID") // student alias: stu
            ->join("t_student as stu", "stu.Stud_ID", "=", "b.Stud_ID") // student alias: stu
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID") // <-- use b.Subject_ID
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->select([
                "b.Booking_ID",
                "p.Name as Professor", // student name
                "subj.Subject_Name as subject",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as type"),
                "b.Booking_Date",
                "b.Mode",
                "b.Created_At",
                "b.Status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
            ])
            ->orderByRaw("STR_TO_DATE(b.Booking_Date, '%a %b %d %Y') asc");

        // Filter based on user type
        if (isset($user->Stud_ID)) {
            $query->where("b.Stud_ID", $user->Stud_ID);
        } elseif (isset($user->Prof_ID)) {
            $query->where("b.Prof_ID", $user->Prof_ID);
        }

        $bookings = $query->get();
        return view("conlog", compact("bookings"));
    }

    public function apiBookings()
    {
        $user = Auth::user();
        $query = DB::table("t_consultation_bookings as b")
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->select([
                "b.Booking_ID",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as Type"),
                "b.Booking_Date",
                "b.Status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
            ]);

        if (isset($user->Stud_ID)) {
            $query->where("b.Stud_ID", $user->Stud_ID);
        } elseif (isset($user->Prof_ID)) {
            $query->where("b.Prof_ID", $user->Prof_ID);
        }

        $bookings = $query->get();

        return response()->json($bookings);
    }

    public function getBookings()
    {
        $user = Auth::user();

        $query = DB::table("t_consultation_bookings as b")
            ->join("professors as p", "p.Prof_ID", "=", "b.Prof_ID")
            ->join("t_student as stu", "stu.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->select([
                "b.Booking_ID",
                "p.Name as Professor",
                "subj.Subject_Name as subject",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as type"),
                "b.Booking_Date",
                "b.Mode",
                "b.Created_At",
                "b.Status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
            ])
            ->orderByRaw("STR_TO_DATE(b.Booking_Date, '%a %b %d %Y') desc");

        // Filter based on user type
        if (isset($user->Stud_ID)) {
            $query->where("b.Stud_ID", $user->Stud_ID);
        } elseif (isset($user->Prof_ID)) {
            $query->where("b.Prof_ID", $user->Prof_ID);
        }

        $bookings = $query->get();
        return response()->json($bookings);
    }

    /**
     * Get ALL consultations for admin dashboard (no filtering by user)
     */
    public function getAllConsultations()
    {
        $query = DB::table("t_consultation_bookings as b")
            ->join("professors as p", "p.Prof_ID", "=", "b.Prof_ID")
            ->join("t_student as stu", "stu.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->select([
                "b.Booking_ID",
                "stu.Name as student",
                "p.Name as professor",
                "subj.Subject_Name as subject",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as type"),
                "b.Booking_Date",
                "b.Mode",
                "b.Created_At",
                "b.Status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
            ])
            ->orderByRaw("STR_TO_DATE(b.Booking_Date, '%a %b %d %Y') desc");

        // NO user filtering - admin sees ALL consultations
        $bookings = $query->get();
        return response()->json($bookings);
    }

    /**
     * Get detailed consultation information by booking ID for admin
     */
    public function getConsultationDetails($bookingId)
    {
        $consultation = DB::table("t_consultation_bookings as b")
            ->join("professors as p", "p.Prof_ID", "=", "b.Prof_ID")
            ->join("t_student as stu", "stu.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->select([
                "b.Booking_ID as booking_id",
                "stu.Name as student_name",
                "p.Name as professor_name",
                "subj.Subject_Name as subject",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as type"),
                "b.Booking_Date as booking_date",
                "b.Mode as mode",
                "b.Created_At as created_at",
                "b.Status as status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
            ])
            ->where("b.Booking_ID", $bookingId)
            ->first();

        if (!$consultation) {
            return response()->json(["error" => "Consultation not found"], 404);
        }

        return response()->json($consultation);
    }
}
