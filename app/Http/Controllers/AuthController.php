<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Schema;
use App\Models\User; // or your custom Model if different

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $studIdInput = (string) $request->Stud_ID;
        $normalizedId = Str::lower(trim($studIdInput));
        $attemptKey = "login:student:" . $normalizedId . ":" . $request->ip();
        $lockKey = "loginlock:student:" . $normalizedId . ":" . $request->ip();
        $maxAttempts = (int) config("auth_security.rate_limit_max_attempts", 5);
        $decay = (int) config("auth_security.rate_limit_decay", 60);

        // Basic validation first
        $request->validate(
            [
                "Stud_ID" => "required|string|max:9",
                "Password" => "required",
            ],
            [
                "Stud_ID.max" => "Student ID must not exceed 9 characters.",
            ],
        );

        // If currently locked, enforce full remaining time
        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $remain = RateLimiter::availableIn($lockKey);
            return back()
                ->withErrors(["login" => "Too many attempts. Try again in " . $remain . "s."])
                ->with("lock_until_student", time() + $remain)
                ->withInput($request->only("Stud_ID"));
        }

        Log::info("Student login attempt", [
            "stud_id" => $studIdInput,
            "ip" => $request->ip(),
        ]);

        $studId = trim($studIdInput);
        $user = User::whereRaw("RTRIM(Stud_ID) = ?", [$studId])->first();

        if (!$user) {
            // Specific: student ID doesn't exist
            RateLimiter::hit($attemptKey, $decay);
            // Check if threshold reached
            if (RateLimiter::attempts($attemptKey) >= $maxAttempts) {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay); // start lock (single token with decay TTL)
                $this->recordAttempt($studId, $request, false, "locked_id_not_found");
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_student", time() + $decay)
                    ->withInput($request->only("Stud_ID"));
            }
            Log::notice("Login failed - id not found", ["stud_id" => $studId]);
            $this->recordAttempt($studId, $request, false, "not_found");
            return back()
                ->withErrors(["login" => "Student ID does not exist."])
                ->withInput($request->only("Stud_ID"));
        }

        // Optional inactive flag support (tolerate missing column)
        if (isset($user->is_active) && (int) $user->is_active === 0) {
            RateLimiter::hit($attemptKey, $decay);
            if (RateLimiter::attempts($attemptKey) >= $maxAttempts) {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                $this->recordAttempt($studId, $request, false, "locked_inactive");
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_student", time() + $decay);
            }
            Log::notice("Login failed - inactive", ["stud_id" => $studId]);
            $this->recordAttempt($studId, $request, false, "inactive");
            return back()->withErrors(["login" => "Account is inactive."]);
        }

        $incoming = trim((string) $request->Password);
        $storedT = trim((string) $user->Password);
        $valid = false;
        $mode = "plain";
        try {
            if (
                str_starts_with($storedT, '$2y$') ||
                str_starts_with($storedT, '$2b$') ||
                str_starts_with($storedT, '$2a$')
            ) {
                $mode = "bcrypt";
                $valid = Hash::check($incoming, $storedT);
            } else {
                $valid = hash_equals($storedT, $incoming);
            }
        } catch (\Throwable $e) {
            $valid = hash_equals($storedT, $incoming);
        }

        if (!$valid) {
            RateLimiter::hit($attemptKey, $decay);
            if (RateLimiter::attempts($attemptKey) >= $maxAttempts) {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                Log::notice("Student locked after bad password threshold", ["stud_id" => $studId]);
                $this->recordAttempt($studId, $request, false, "locked_bad_password");
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_student", time() + $decay)
                    ->withInput($request->only("Stud_ID"));
            }
            Log::notice("Login failed - bad password", ["stud_id" => $studId, "mode" => $mode]);
            $this->recordAttempt($studId, $request, false, "bad_password");
            return back()
                ->withErrors(["login" => "Incorrect password."])
                ->withInput($request->only("Stud_ID"));
        }
        // Success: clear attempts + any lock just in case
        RateLimiter::clear($attemptKey);
        RateLimiter::clear($lockKey);

        // Remember me only if column exists to avoid SQL error on legacy schema
        $remember = false;
        if ($request->boolean("remember")) {
            try {
                if (
                    Schema::hasTable($user->getTable()) &&
                    Schema::hasColumn($user->getTable(), $user->getRememberTokenName())
                ) {
                    $remember = true;
                }
            } catch (\Throwable $e) {
                /* ignore */
            }
        }
        Auth::login($user, $remember);
        $request->session()->regenerate();
        Log::info("Student login success", ["stud_id" => $studId, "remember" => $remember]);
        $this->recordAttempt($studId, $request, true, "success");
        return redirect()->intended(route("dashboard"));
    }

    protected function recordAttempt(
        string $studId,
        Request $request,
        bool $success,
        string $reason,
    ): void {
        try {
            if (!Schema::hasTable("login_attempts")) {
                return;
            }
            LoginAttempt::create([
                "stud_id" => $studId,
                "ip" => $request->ip(),
                "user_agent" => substr((string) $request->userAgent(), 0, 250),
                "successful" => $success,
                "reason" => $reason,
            ]);
        } catch (\Throwable $e) {
            // Silent fail; avoid breaking login flow
        }
    }

    public function logout(Request $request)
    {
        // If somehow a professor hits this route, log them out correctly
        if (Auth::guard("professor")->check()) {
            Auth::guard("professor")->logout();
        }
        if (Auth::check()) {
            Auth::logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route("login");
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

        // Get the currently authenticated user
        $user = Auth::user();

        // PRIORITY CHECK: Verify current password first before other validations
        if ($user->Password != $request->oldPassword) {
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

        // Save the changes to the database
        $user->save();

        // Redirect back with success message
        return back()->with("password_status", "Password changed successfully!");
    }
}
