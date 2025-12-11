<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\LoginAttempt;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UnifiedAuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::guard("admin")->check()) {
            return redirect()->route("admin.dashboard");
        }
        if (Auth::guard("professor")->check()) {
            return redirect()->route("dashboard.professor");
        }
        if (Auth::check()) {
            return redirect()->route("dashboard");
        }

        return view("login");
    }

    public function login(Request $request)
    {
        $request->validate(
            [
                "username" => ["required", "string", "max:32"],
                "password" => ["required", "string"],
            ],
            [
                "username.required" => "Username is required.",
            ],
        );

        $username = trim((string) $request->input("username", ""));
        $password = (string) $request->input("password", "");
        $rememberRequested = $request->boolean("remember");
        $ip = $request->ip();
        $normalized = Str::lower($username);

        $attemptKey = "login:unified:" . $normalized . ":" . $ip;
        $lockKey = "loginlock:unified:" . $normalized . ":" . $ip;
        $maxAttempts = (int) config("auth_security.rate_limit_max_attempts", 5);
        $decay = (int) config("auth_security.rate_limit_decay", 60);

        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $remaining = RateLimiter::availableIn($lockKey);
            return back()
                ->withErrors(["login" => "Too many attempts. Try again in " . $remaining . "s."])
                ->with("lock_until", time() + $remaining)
                ->withInput(["username" => $username]);
        }

        Log::info("Unified login attempt", ["username" => $username, "ip" => $ip]);

        $account = $this->resolveAccount($username);

        if ($account === null) {
            RateLimiter::hit($attemptKey, $decay);
            $reason =
                RateLimiter::attempts($attemptKey) >= $maxAttempts
                    ? "locked_not_found"
                    : "not_found";
            $this->recordAttempt(
                $this->selectAuditIdentifier($username),
                "unknown",
                $request,
                false,
                $reason,
            );

            if ($reason === "locked_not_found") {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until", time() + $decay)
                    ->withInput(["username" => $username]);
            }

            Log::notice("Unified login failed - username not found", ["username" => $username]);
            return back()
                ->withErrors(["login" => "Username does not exist."])
                ->withInput(["username" => $username]);
        }

        $user = $account["user"];
        $role = $account["role"];
        $guard = $account["guard"];
        $identifier = $account["identifier"];

        if (isset($user->is_active) && (int) $user->is_active === 0) {
            RateLimiter::hit($attemptKey, $decay);
            $reason =
                RateLimiter::attempts($attemptKey) >= $maxAttempts ? "locked_inactive" : "inactive";
            $this->recordAttempt($identifier, $role, $request, false, $reason);

            if ($reason === "locked_inactive") {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until", time() + $decay)
                    ->withInput(["username" => $username]);
            }

            Log::notice("Unified login failed - inactive", ["role" => $role, "id" => $identifier]);
            return back()
                ->withErrors(["login" => "Account is inactive."])
                ->withInput(["username" => $username]);
        }

        if (!$this->passwordMatches($password, (string) $user->Password)) {
            RateLimiter::hit($attemptKey, $decay);
            $reason =
                RateLimiter::attempts($attemptKey) >= $maxAttempts
                    ? "locked_bad_password"
                    : "bad_password";
            $this->recordAttempt($identifier, $role, $request, false, $reason);

            if ($reason === "locked_bad_password") {
                RateLimiter::clear($attemptKey);
                RateLimiter::hit($lockKey, $decay);
                Log::notice("Unified login locked after repeated bad passwords", [
                    "role" => $role,
                    "id" => $identifier,
                ]);
                return back()
                    ->withErrors(["login" => "Too many attempts. Try again in " . $decay . "s."])
                    ->with("lock_until", time() + $decay)
                    ->withInput(["username" => $username]);
            }

            Log::notice("Unified login failed - bad password", [
                "role" => $role,
                "id" => $identifier,
            ]);
            return back()
                ->withErrors(["login" => "Incorrect password."])
                ->withInput(["username" => $username]);
        }

        RateLimiter::clear($attemptKey);
        RateLimiter::clear($lockKey);

        foreach (["web", "professor", "admin"] as $existingGuard) {
            if ($existingGuard !== $guard && Auth::guard($existingGuard)->check()) {
                Auth::guard($existingGuard)->logout();
            }
        }

        $remember = false;
        if ($rememberRequested && $this->canRemember($user)) {
            $remember = true;
        }

        Auth::guard($guard)->login($user, $remember);
        $request->session()->regenerate();

        $this->recordAttempt($identifier, $role, $request, true, "success");
        Log::info("Unified login success", [
            "role" => $role,
            "id" => $identifier,
            "remember" => $remember,
        ]);

        $redirectRoute = match ($role) {
            "admin" => "admin.dashboard",
            "professor" => "dashboard.professor",
            default => "dashboard",
        };

        return redirect()->intended(route($redirectRoute));
    }

    protected function resolveAccount(string $username): ?array
    {
        $trimmed = trim($username);

        $admin = Admin::whereRaw("RTRIM(Admin_ID) = ?", [$trimmed])->first();
        if ($admin) {
            return [
                "role" => "admin",
                "guard" => "admin",
                "user" => $admin,
                "identifier" => (string) $admin->Admin_ID,
            ];
        }

        $student = User::whereRaw("RTRIM(Stud_ID) = ?", [$trimmed])->first();
        if ($student) {
            return [
                "role" => "student",
                "guard" => "web",
                "user" => $student,
                "identifier" => (string) $student->Stud_ID,
            ];
        }

        if (preg_match('/^\d+$/', $trimmed)) {
            $numeric = ltrim($trimmed, "0");
            $numeric = $numeric === "" ? "0" : $numeric;
            $profId = (int) $numeric;
            $professor = Professor::where("Prof_ID", $profId)->first();
            if ($professor) {
                return [
                    "role" => "professor",
                    "guard" => "professor",
                    "user" => $professor,
                    "identifier" => (string) $professor->Prof_ID,
                ];
            }
        }

        return null;
    }

    protected function passwordMatches(string $incoming, string $stored): bool
    {
        if ($stored === "") {
            return false;
        }

        if (
            str_starts_with($stored, '$2y$') ||
            str_starts_with($stored, '$2b$') ||
            str_starts_with($stored, '$2a$')
        ) {
            return Hash::check($incoming, $stored);
        }

        return hash_equals($stored, $incoming);
    }

    protected function recordAttempt(
        ?string $identifier,
        string $role,
        Request $request,
        bool $success,
        string $reason,
    ): void {
        try {
            if (!Schema::hasTable("login_attempts")) {
                return;
            }

            $data = [
                "ip" => $request->ip(),
                "user_agent" => substr((string) $request->userAgent(), 0, 250),
                "successful" => $success,
                "reason" => $reason,
            ];

            $column = match ($role) {
                "student" => "stud_id",
                "professor" => "prof_id",
                "admin" => "admin_id",
                default => null,
            };

            if (
                $identifier !== null &&
                $column !== null &&
                Schema::hasColumn("login_attempts", $column)
            ) {
                $data[$column] = $identifier;
            }

            LoginAttempt::create($data);
        } catch (\Throwable $e) {
            // Silent fail; audit persistence should not block login flow.
        }
    }

    protected function selectAuditIdentifier(string $username): ?string
    {
        $trimmed = trim($username);

        if (preg_match("/[A-Za-z]/", $trimmed)) {
            return $trimmed;
        }

        return $trimmed === "" ? null : $trimmed;
    }

    protected function canRemember($user): bool
    {
        try {
            $table = $user->getTable();
            $column = method_exists($user, "getRememberTokenName")
                ? $user->getRememberTokenName()
                : "remember_token";

            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
