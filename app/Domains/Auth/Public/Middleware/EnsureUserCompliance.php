<?php

namespace App\Domains\Auth\Public\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCompliance
{
    /**
     * Routes that should bypass compliance checks.
     */
    protected array $except = [
        'logout',
        'compliance.terms.show',
        'compliance.terms.accept',
        'compliance.parental.show',
        'compliance.parental.upload',
        'session.heartbeat',
        'session.csrf',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip compliance check for excepted routes
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        /** @var \App\Domains\Auth\Private\Models\User $user */
        $user = Auth::user();

        // Use session to cache compliance check (only check once per session)
        $sessionKey = 'user_compliance_checked_' . $user->id;
        
        if (!session()->has($sessionKey)) {
            // First check: terms acceptance
            if (!$user->hasAcceptedTerms()) {
                // Store intended URL for redirect after compliance
                if (!$request->routeIs('compliance.*')) {
                    session()->put('url.intended', $request->fullUrl());
                }
                return redirect()->route('compliance.terms.show');
            }

            // Second check: parental authorization for underage users
            if ($user->needsParentalAuthorization()) {
                // Store intended URL for redirect after compliance
                if (!$request->routeIs('compliance.*')) {
                    session()->put('url.intended', $request->fullUrl());
                }
                return redirect()->route('compliance.parental.show');
            }

            // Mark as compliant in session to avoid future checks
            session()->put($sessionKey, true);
        }

        return $next($request);
    }

    /**
     * Determine if the request should bypass compliance checks.
     */
    protected function shouldBypass(Request $request): bool
    {
        foreach ($this->except as $route) {
            if ($request->routeIs($route)) {
                return true;
            }
        }

        return false;
    }
}
