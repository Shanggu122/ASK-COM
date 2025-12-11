<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarOverride;
use App\Models\Notification;
use App\Models\Admin;
use Carbon\Carbon;

class ProfessorCalendarOverrideController extends Controller
{
    // POST /api/professor/calendar/leave/apply
    public function applyLeave(Request $request)
    {
        $prof = auth()->guard("professor")->user();
        if (!$prof) {
            return response()->json(["success" => false, "error" => "Unauthorized"], 401);
        }

        $data = $request->validate([
            "start_date" => "required|date",
        ]);

        $date = Carbon::parse($data["start_date"])->toDateString();
        $profId = (int) ($prof->Prof_ID ?? 0);

        // Avoid duplicates: if an identical professor block exists for this date, return it as success
        $existing = CalendarOverride::query()
            ->where("scope_type", "professor")
            ->where("scope_id", $profId)
            ->where("effect", "block_all")
            ->where(function ($q) use ($date) {
                $q->where("start_date", "<=", $date)->where("end_date", ">=", $date);
            })
            ->first();
        if ($existing) {
            return response()->json([
                "success" => true,
                "override_id" => $existing->id,
                "existed" => true,
            ]);
        }

        $override = CalendarOverride::create([
            "start_date" => $date,
            "end_date" => $date,
            "scope_type" => "professor",
            "scope_id" => $profId,
            "effect" => "block_all",
            "allowed_mode" => null,
            "reason_key" => "prof_leave",
            "reason_text" => "Leave",
            "created_by" => null, // professor-initiated
        ]);
        // Create an admin-visible notification entry (no booking_id) so admin feed shows it
        try {
            // Use a conventional type key
            $title = "Professor Leave Day";
            $message = "Professor {$prof->Name} set a leave on {$date}.";
            // Create one notification row that admins can see via admin feed endpoints
            Notification::create([
                "user_id" => (int) ($prof->Prof_ID ?? 0),
                "booking_id" => 0,
                "type" => "professor_leave",
                "title" => $title,
                "message" => $message,
                "is_read" => false,
            ]);
        } catch (\Throwable $e) {
            // Non-fatal for the API
        }

        return response()->json(["success" => true, "override_id" => $override->id]);
    }

    // POST /api/professor/calendar/leave/remove
    public function removeLeave(Request $request)
    {
        $prof = auth()->guard("professor")->user();
        if (!$prof) {
            return response()->json(["success" => false, "error" => "Unauthorized"], 401);
        }

        $data = $request->validate([
            "start_date" => "required|date",
        ]);

        $date = Carbon::parse($data["start_date"])->toDateString();
        $profId = (int) ($prof->Prof_ID ?? 0);

        // Only remove professor-scoped leave blocks created by professor (identified via reason_key)
        $deleted = CalendarOverride::query()
            ->where("scope_type", "professor")
            ->where("scope_id", $profId)
            ->where("effect", "block_all")
            ->where("reason_key", "prof_leave")
            ->where(function ($q) use ($date) {
                $q->where("start_date", "<=", $date)->where("end_date", ">=", $date);
            })
            ->delete();
        // Optionally notify admin about removal as well
        if ($deleted) {
            try {
                $title = "Professor Leave Removed";
                $message = "Professor {$prof->Name} removed leave on {$date}.";
                Notification::create([
                    "user_id" => (int) ($prof->Prof_ID ?? 0),
                    "booking_id" => 0,
                    "type" => "professor_leave_removed",
                    "title" => $title,
                    "message" => $message,
                    "is_read" => false,
                ]);
            } catch (\Throwable $e) {
            }
        }

        return response()->json(["success" => true, "deleted" => $deleted]);
    }
}
