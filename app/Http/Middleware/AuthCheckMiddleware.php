<?php

namespace App\Http\Middleware;

use Closure;

class AuthCheckMiddleware
{
    /**
     * Handle an incoming request.
     * Redirect to login if user is not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!session()->has('authenticated')) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login')->with('error', __('app.login_required'));
        }

        return $next($request);
    }
}
