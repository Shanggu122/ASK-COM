<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminSubjectController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::query()
            ->orderBy("Subject_Name")
            ->get(["Subject_ID", "Subject_Name"])
            ->map(function ($subject) {
                return [
                    "id" => (int) $subject->Subject_ID,
                    "name" => (string) $subject->Subject_Name,
                ];
            });

        return response()->json([
            "success" => true,
            "subjects" => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "name" => "required|string|max:100",
        ]);

        $trimmedName = trim($data["name"]);
        if ($trimmedName === "") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Subject name cannot be empty.",
                ],
                422,
            );
        }

        $exists = DB::table("t_subject")
            ->whereRaw("LOWER(TRIM(Subject_Name)) = ?", [strtolower($trimmedName)])
            ->exists();

        if ($exists) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Subject already exists.",
                ],
                422,
            );
        }

        try {
            $id = DB::table("t_subject")->insertGetId([
                "Subject_Name" => $trimmedName,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to create subject", ["error" => $e->getMessage()]);
            return response()->json(
                [
                    "success" => false,
                    "message" => "Failed to create subject.",
                ],
                500,
            );
        }

        return response()->json([
            "success" => true,
            "subject" => [
                "id" => (int) $id,
                "name" => $trimmedName,
            ],
        ]);
    }

    public function destroy(int $subjectId)
    {
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Subject not found.",
                ],
                404,
            );
        }

        $inBookings = DB::table("t_consultation_bookings")
            ->where("Subject_ID", $subject->Subject_ID)
            ->exists();
        if ($inBookings) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Cannot delete a subject with existing consultations.",
                ],
                422,
            );
        }

        $inAssignments = DB::table("professor_subject")
            ->where("Subject_ID", $subject->Subject_ID)
            ->exists();
        if ($inAssignments) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Remove this subject from all professors before deleting.",
                ],
                422,
            );
        }

        try {
            $subject->delete();
        } catch (\Throwable $e) {
            Log::error("Failed to delete subject", ["error" => $e->getMessage()]);
            return response()->json(
                [
                    "success" => false,
                    "message" => "Failed to delete subject.",
                ],
                500,
            );
        }

        return response()->json([
            "success" => true,
        ]);
    }
}
