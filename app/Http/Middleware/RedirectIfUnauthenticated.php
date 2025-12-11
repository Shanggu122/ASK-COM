<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUnauthenticated
{
    /**
     * Handle an unauthenticated user.
     * This mirrors Laravel's Authenticate middleware but sends users to '/'.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null; // Let the framework generate JSON 401
        }
        return route("login");
    }

    /**
     * Convert the exception to a response.
     */
    public function handle($request, \Closure $next, ...$guards)
    {
        try {
            return $next($request);
        } catch (AuthenticationException $e) {
            $redirect = $this->redirectTo($request);
            if ($redirect === null) {
                throw $e; // JSON case
            }
            return redirect()->guest($redirect);
        }
    }
}
