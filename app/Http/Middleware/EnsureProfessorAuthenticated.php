<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureProfessorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('professor')->check()) {
            return redirect('/login-professor');
        }
        return $next($request);
    }
}
