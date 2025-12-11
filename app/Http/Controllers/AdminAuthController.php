<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Admin;
use App\Models\LoginAttempt;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        // Prevent caching of login form
        return response()
            ->view("login-admin")
            ->header("Cache-Control", "no-cache, no-store, max-age=0, must-revalidate")
            ->header("Pragma", "no-cache")
            ->header("Expires", "Fri, 01 Jan 1990 00:00:00 GMT");
    }

    public function login(Request $request)
    {
        $request->validate(
            [
                "Admin_ID" => ["required", "string", "max:9", 'regex:/^[A-Za-z0-9]+$/'],
                "Password" => "required|string",
            ],
            [
                "Admin_ID.max" => "Admin ID must not exceed 9 characters.",
                "Admin_ID.regex" => "Admin ID may only contain letters and numbers.",
            ],
        );

        $adminIdInput = (string) $request->Admin_ID;
        $adminIdInput = (string) $request->Admin_ID;
        $normalized = Str::lower(trim($adminIdInput));
        $attemptKey = "login:admin:" . $normalized . ":" . $request->ip();
        $lockKey = "loginlock:admin:" . $normalized . ":" . $request->ip();
        $maxAttempts = (int) config("auth_security.rate_limit_max_attempts", 5);
        $decay = (int) config("auth_security.rate_limit_decay", 60);

        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $remain = RateLimiter::availableIn($lockKey);
            return back()
                ->withErrors(["login" => "Too many attempts. Try again in " . $remain . "s."])
                ->with("lock_until_admin", time() + $remain)
                ->withInput($request->only("Admin_ID"));
        }

        $adminId = trim($adminIdInput);
        $admin = Admin::whereRaw("RTRIM(Admin_ID) = ?", [$adminId])->first();
        if (!$admin) {
            RateLimiter::hit($attemptKey, $decay);
            if (RateLimiter::attempts($attemptKey) >= $maxAttempts) {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                $this->recordAttemptAdmin($adminId, $request, false, "locked_id_not_found");
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_admin", time() + $decay)
                    ->withInput($request->only("Admin_ID"));
            }
            Log::notice("Admin login failed - id not found", ["admin_id" => $adminId]);
            $this->recordAttemptAdmin($adminId, $request, false, "not_found");
            return back()
                ->withErrors(["Admin_ID" => "Admin ID does not exist."])
                ->withInput($request->only("Admin_ID"));
        }

        // Optional inactive flag support (tolerate missing column)
        if (isset($admin->is_active) && (int) $admin->is_active === 0) {
            RateLimiter::hit($attemptKey, $decay);
            if (RateLimiter::attempts($attemptKey) >= $maxAttempts) {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                $this->recordAttemptAdmin($adminId, $request, false, "locked_inactive");
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_admin", time() + $decay);
            }
            Log::notice("Admin login failed - inactive", ["admin_id" => $adminId]);
            $this->recordAttemptAdmin($adminId, $request, false, "inactive");
            return back()->withErrors(["login" => "Account is inactive."]);
        }

        $incoming = trim((string) $request->Password);
        $storedT = trim((string) $admin->Password);
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
                $this->recordAttemptAdmin($adminId, $request, false, "locked_bad_password");
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until_admin", time() + $decay)
                    ->withInput($request->only("Admin_ID"));
            }
            Log::notice("Admin login failed - bad password", [
                "admin_id" => $adminId,
                "mode" => $mode,
            ]);
            $this->recordAttemptAdmin($adminId, $request, false, "bad_password");
            return back()
                ->withErrors(["Password" => "Incorrect password."])
                ->withInput($request->only("Admin_ID"));
        }

        RateLimiter::clear($attemptKey);
        RateLimiter::clear($lockKey);

        $remember = false;
        if ($request->boolean("remember")) {
            try {
                if (
                    Schema::hasTable($admin->getTable()) &&
                    Schema::hasColumn($admin->getTable(), "remember_token")
                ) {
                    $remember = true;
                }
            } catch (\Throwable $e) {
            }
        }
        Auth::guard("admin")->login($admin, $remember);
        $request->session()->regenerate();
        Log::info("Admin login success", ["admin_id" => $adminId, "remember" => $remember]);
        $this->recordAttemptAdmin($adminId, $request, true, "success");
        return redirect()->intended(route("admin.dashboard"));
    }

    public function logout(Request $request)
    {
        if (Auth::guard("admin")->check()) {
            Auth::guard("admin")->logout();
        }
        if (Auth::check()) {
            // safety in case default guard also set
            Auth::logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route("login");
    }

    protected function recordAttemptAdmin(
        string $adminId,
        Request $request,
        bool $success,
        string $reason,
    ): void {
        try {
            if (!Schema::hasTable("login_attempts")) {
                return;
            }
            LoginAttempt::create([
                "admin_id" => $adminId,
                "ip" => $request->ip(),
                "user_agent" => substr((string) $request->userAgent(), 0, 250),
                "successful" => $success,
                "reason" => $reason,
            ]);
        } catch (\Throwable $e) {
            // silent fail
        }
    }
}
