<?php

namespace App\Domains\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Domains\Auth\Models\User $user */
        $user = Auth::user();
        
        // If no specific roles are provided, just check if user is authenticated
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has any of the required roles
        if ($user->hasRole($roles)) {
            return $next($request);
        }

        // If user is an admin, allow access to all routes
        if ($user->isAdmin()) {
            return $next($request);
        }

        // If no role matches, redirect to home with error
        return redirect()->route('dashboard')
            ->with('error', 'You do not have permission to access this page.');
    }
}
