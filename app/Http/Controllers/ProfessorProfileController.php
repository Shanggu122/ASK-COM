<?php

// In ProfileController-professor.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Professor;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class ProfessorProfileController extends Controller
{
    public function show()
    {
        $user = Auth::guard("professor")->user();
        $profId = Session::get("Prof_ID");

        return view("profile-professor", compact("user"));
    }

    public function changePassword(Request $request)
    {
        // First, validate only required fields and current password
        $request->validate(
            [
                "oldPassword" => "required",
                "newPassword" => "required",
                "newPassword_confirmation" => "required",
            ],
            [
                "oldPassword.required" => "Current password is required.",
                "newPassword.required" => "New password is required.",
                "newPassword_confirmation.required" => "Password confirmation is required.",
            ],
        );

        // Get the authenticated user
        $user = Auth::guard("professor")->user();

        // PRIORITY CHECK: Verify current password first before other validations
        if ($user->Password !== $request->oldPassword) {
            return back()->withErrors([
                "oldPassword" =>
                    "Your current password is incorrect. Please enter your existing password correctly.",
            ]);
        }

        // Only after current password is verified, check other password requirements
        $request->validate(
            [
                "newPassword" => "min:8|confirmed",
            ],
            [
                "newPassword.min" =>
                    "Your new password is too short. It must be at least 8 characters long.",
                "newPassword.confirmed" =>
                    "Your new password and confirmation password do not match. Please re-enter them correctly.",
            ],
        );

        // Check if new password is different from old password
        if ($request->newPassword === $request->oldPassword) {
            return back()->withErrors([
                "newPassword" => "Your new password must be different from your current password.",
            ]);
        }

        // Update the password
        $user->Password = $request->newPassword;
        $user->save();

        // Redirect back with success message
        return back()->with("password_status", "Password changed successfully!");
    }

    public function uploadPicture(Request $request)
    {
        $request->validate([
            "profile_picture" => "required|image|mimes:jpeg,png,jpg|max:2048",
        ]);

        $user = Auth::guard("professor")->user();
        // Let model mutator handle storage and attribute
        $user->setAttribute("profile_picture", $request->file("profile_picture"));
        $user->save();

        return back()->with("status", "Profile picture updated!");
    }

    public function deletePicture(Request $request)
    {
        $user = Auth::guard("professor")->user();
        if ($user->profile_picture) {
            Storage::disk("public")->delete($user->profile_picture);
            $user->setAttribute("profile_picture", null);
            $user->save();
            return response()->json(["success" => true]);
        }
        return response()->json(["success" => false]);
    }

    /**
     * Update the professor's schedule text (newline-separated).
     */
    public function updateSchedule(Request $request)
    {
        $user = Auth::guard("professor")->user();
        if (!$user) {
            return redirect()->route("login");
        }

        $validated = $request->validate(
            [
                "schedule" => ["nullable", "string", "max:10000"],
            ],
            [
                "schedule.max" => "Schedule is too long. Please keep it under 10,000 characters.",
            ],
        );

        $text = (string) ($validated["schedule"] ?? "");
        // Normalize line endings and trim each line
        $text = preg_replace("/(\r\n|\r|\n)/", "\n", $text);
        $lines = array_map("trim", $text === "" ? [] : explode("\n", $text));
        // Collapse multiple empty lines
        $clean = "";
        if (!empty($lines)) {
            $cleanLines = [];
            foreach ($lines as $l) {
                if ($l === "" && end($cleanLines) === "") {
                    continue;
                }
                $cleanLines[] = $l;
            }
            $clean = implode("\n", $cleanLines);
        }

        $user->Schedule = $clean !== "" ? $clean : null;
        $user->save();

        return back()->with("status", "Schedule updated.");
    }
}
