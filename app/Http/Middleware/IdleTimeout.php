<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdleTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $timeout = (int) config('auth_security.idle_timeout_minutes', 30) * 60; // seconds
            $lastActivity = session('last_activity_timestamp');
            $now = time();
            if ($lastActivity && ($now - $lastActivity) > $timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors(['login' => 'You were logged out due to inactivity.']);
            }
            session(['last_activity_timestamp' => $now]);
        }
        return $next($request);
    }
}
