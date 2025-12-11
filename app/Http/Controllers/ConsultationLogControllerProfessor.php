<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Services\AcademicTermService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Add this
use Illuminate\Support\Facades\DB;

class ConsultationLogControllerProfessor extends Controller
{
    // app/Http/Controllers/ConsultationLogController-professor.php
    public function index()
    {
        $user = Auth::guard("professor")->user();
        $bookings = DB::table("t_consultation_bookings as b")
            ->join("t_student as stu", "stu.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID") // FIXED LINE
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->leftJoin("terms as t", "t.id", "=", "b.term_id")
            ->select([
                "b.Booking_ID",
                "stu.Stud_ID as student_id",
                "stu.Name as student", // student name
                "subj.Subject_Name as subject",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as type"), // Show custom type if present
                "b.Booking_Date",
                "b.Mode",
                DB::raw("DATE_FORMAT(b.Created_At, '%m/%d/%Y %r') as Created_At"), // 12-hour format with AM/PM
                "b.Status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
                "b.term_id",
                "t.name as term_name",
                "t.sequence as term_sequence",
            ])
            ->where("b.Prof_ID", $user->Prof_ID)
            ->orderByRaw("STR_TO_DATE(b.Booking_Date, '%a %b %d %Y') asc")
            ->get();

        /** @var AcademicTermService $termService */
        $termService = app(AcademicTermService::class);
        $activeTerm = $termService->getActiveTerm();
        $termOptions = Term::query()->with("academicYear")->orderByDesc("start_at")->get();

        return view("conlog-professor", compact("bookings", "termOptions", "activeTerm"));
    }

    // This method will be responsible for returning booking data in JSON format
    public function getBookings(Request $request)
    {
        $user = Auth::guard("professor")->user();

        $bookings = DB::table("t_consultation_bookings as b")
            ->join("t_student as stu", "stu.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->leftJoin("terms as t", "t.id", "=", "b.term_id")
            ->select([
                "b.Booking_ID",
                "stu.Stud_ID as student_id",
                "stu.Name as student",
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
                "b.term_id",
                "t.name as term_name",
                "t.sequence as term_sequence",
            ])
            ->where("b.Prof_ID", $user->Prof_ID)
            ->when($request->query("term_id"), function ($query, $termId) {
                if ($termId === "active") {
                    $active = app(AcademicTermService::class)->getActiveTerm();
                    if ($active) {
                        return $query->where("b.term_id", $active->id);
                    }
                    return $query;
                }
                if ($termId === "unassigned") {
                    return $query->whereNull("b.term_id");
                }
                return $query->where("b.term_id", $termId);
            })
            ->orderByRaw("STR_TO_DATE(b.Booking_Date, '%a %b %d %Y') desc")
            ->get();

        return response()->json($bookings);
    }

    public function apiBookings()
    {
        $user = Auth::guard("professor")->user();

        $bookings = DB::table("t_consultation_bookings as b")
            ->join("t_student as stu", "stu.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->leftJoin("terms as t", "t.id", "=", "b.term_id")
            ->select([
                "b.Booking_ID",
                "stu.Stud_ID as student_id",
                "stu.Name as student",
                "subj.Subject_Name as subject",
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as type"),
                "b.Booking_Date",
                "b.Mode",
                DB::raw("DATE_FORMAT(b.Created_At, '%m/%d/%Y %r') as Created_At"), // 12-hour format with AM/PM
                "b.Status",
                "b.completion_reason",
                "b.completion_requested_at",
                "b.completion_reviewed_at",
                "b.completion_student_response",
                "b.completion_student_comment",
                "b.term_id",
                "t.name as term_name",
                "t.sequence as term_sequence",
            ])
            ->where("b.Prof_ID", $user->Prof_ID)
            ->orderBy("b.Created_At", "asc")
            ->get();

        return response()->json($bookings);
    }
}
