<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventBackHistory
{
    /**
     * Handle an incoming request.
     * This sets no-store/no-cache headers so that after logout
     * pressing the browser back button will force revalidation
     * and redirect to login instead of showing a cached dashboard.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        return $response
            ->header("Cache-Control", "no-cache, no-store, max-age=0, must-revalidate")
            ->header("Pragma", "no-cache")
            ->header("Expires", "Mon, 01 Jan 1990 00:00:00 GMT");
    }
}
