<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Professor;
use App\Models\LoginAttempt;

class AuthControllerProfessor extends Controller
{
    public function login(Request $request)
    {
        $request->validate(
            [
                // digits-only up to 9 to avoid internal spaces and allow leading zeros
                "Prof_ID" => ["required", 'regex:/^\d{1,9}$/'],
                "Password" => "required|string",
            ],
            [
                "Prof_ID.regex" => "Professor ID must be numeric and up to 9 digits.",
            ],
        );

        $profIdInput = (string) $request->Prof_ID;
        $normalized = Str::lower(trim($profIdInput));
        $attemptKey = "login:prof:" . $normalized . ":" . $request->ip();
        $lockKey = "loginlock:prof:" . $normalized . ":" . $request->ip();
        $maxAttempts = (int) config("auth_security.rate_limit_max_attempts", 5);
        $decay = (int) config("auth_security.rate_limit_decay", 60);

        // Existing lock
        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $remain = RateLimiter::availableIn($lockKey);
            return back()
                ->withErrors(["login" => "Too many attempts. Try again in " . $remain . "s."])
                ->with("lock_until_prof", time() + $remain)
                ->withInput($request->only("Prof_ID"));
        }

        // Normalize ID: treat as integer key to accept leading zeros in input
        $profId = trim($profIdInput);
        $profKey = (int) ltrim($profId, "0");
        $user = Professor::where("Prof_ID", $profKey)->first();
        if (!$user) {
            RateLimiter::hit($attemptKey, $decay);
            if (RateLimiter::attempts($attemptKey) >= $maxAttempts) {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                $this->recordAttemptProf($profId, $request, false, "locked_id_not_found");
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_prof", time() + $decay)
                    ->withInput($request->only("Prof_ID"));
            }
            Log::notice("Professor login failed - id not found", ["prof_id" => $profId]);
            $this->recordAttemptProf($profId, $request, false, "not_found");
            return back()
                ->withErrors(["Prof_ID" => "Professor ID does not exist."])
                ->withInput($request->only("Prof_ID"));
        }

        // Optional inactive flag support
        if (isset($user->is_active) && (int) $user->is_active === 0) {
            RateLimiter::hit($attemptKey, $decay);
            if (RateLimiter::attempts($attemptKey) >= $maxAttempts) {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                $this->recordAttemptProf(
                    (string) $user->Prof_ID,
                    $request,
                    false,
                    "locked_inactive",
                );
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_prof", time() + $decay);
            }
            Log::notice("Professor login failed - inactive", ["prof_id" => $user->Prof_ID]);
            $this->recordAttemptProf((string) $user->Prof_ID, $request, false, "inactive");
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
                Log::notice("Professor locked after bad password threshold", [
                    "prof_id" => $profId,
                ]);
                $this->recordAttemptProf(
                    (string) $user->Prof_ID,
                    $request,
                    false,
                    "locked_bad_password",
                );
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_prof", time() + $decay)
                    ->withInput($request->only("Prof_ID"));
            }
            Log::notice("Professor login failed - bad password", [
                "prof_id" => $profId,
                "mode" => $mode,
            ]);
            $this->recordAttemptProf((string) $user->Prof_ID, $request, false, "bad_password");
            return back()
                ->withErrors(["Password" => "Incorrect password."])
                ->withInput($request->only("Prof_ID"));
        }

        RateLimiter::clear($attemptKey);
        RateLimiter::clear($lockKey);

        $remember = false;
        if ($request->boolean("remember")) {
            try {
                if (
                    Schema::hasTable($user->getTable()) &&
                    Schema::hasColumn($user->getTable(), "remember_token")
                ) {
                    $remember = true;
                }
            } catch (\Throwable $e) {
                /* ignore */
            }
        }
        Auth::guard("professor")->login($user, $remember);
        $request->session()->regenerate();
        Log::info("Professor login success", ["prof_id" => $profId, "remember" => $remember]);
        $this->recordAttemptProf((string) $user->Prof_ID, $request, true, "success");
        return redirect()->intended(route("dashboard.professor"));
    }

    protected function recordAttemptProf(
        string $profId,
        Request $request,
        bool $success,
        string $reason,
    ): void {
        try {
            if (!Schema::hasTable("login_attempts")) {
                return;
            }
            LoginAttempt::create([
                "prof_id" => $profId,
                "ip" => $request->ip(),
                "user_agent" => substr((string) $request->userAgent(), 0, 250),
                "successful" => $success,
                "reason" => $reason,
            ]);
        } catch (\Throwable $e) {
            // Silent fail
        }
    }

    public function logout(Request $request)
    {
        if (Auth::guard("professor")->check()) {
            Auth::guard("professor")->logout();
        }
        // Also logout default guard if somehow set to avoid mixed sessions
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

        // Get the authenticated user
        $user = Auth::guard("professor")->user();

        // PRIORITY CHECK: Verify current password first before other validations
        if ($request->oldPassword !== $user->Password) {
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

        // Save new password as plain text
        $user->Password = $request->newPassword;
        $user->save();

        return back()->with("password_status", "Password changed successfully!");
    }
}
