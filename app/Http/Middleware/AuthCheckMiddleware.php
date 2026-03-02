<?php

namespace App\Http\Middleware;

use Closure;

class AuthCheckMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!session()->has('authenticated')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('login')->with('error', __('app.login_required'));
        }

        return $next($request);
    }
}
