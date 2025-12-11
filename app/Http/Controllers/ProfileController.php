<?php

// In ProfileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User; // Make sure your User model is imported

class ProfileController extends Controller
{
    public function show()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Return the profile view with user data
        return view("profile", compact("user"));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            "oldPassword" => "required|string",
            "newPassword" => "required|string|min:8|confirmed",
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Check if the old password is correct
        if (!Hash::check($request->oldPassword, $user->password)) {
            return back()->withErrors([
                "oldPassword" => "The provided password does not match our records.",
            ]);
        }

        // Update the password
        $user->password = Hash::make($request->newPassword);
        $user->save();

        // Redirect back with success message
        return back()->with("password_status", "Password updated successfully!");
    }

    public function uploadPicture(Request $request)
    {
        $request->validate([
            "profile_picture" => "required|image|mimes:jpeg,png,jpg|max:2048",
        ]);

        $user = Auth::user();
        // Let model mutator handle storage and attribute
        $user->setAttribute("profile_picture", $request->file("profile_picture"));
        $user->save();

        return back()->with("status", "Profile picture updated!");
    }

    public function deletePicture(Request $request)
    {
        $user = Auth::user();
        if ($user->profile_picture) {
            Storage::disk("public")->delete($user->profile_picture);
            $user->setAttribute("profile_picture", null);
            $user->save();
            return response()->json(["success" => true]);
        }
        return response()->json(["success" => false]);
    }
}
