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
                $this->rememberIntended($request);
                return redirect()->route('compliance.terms.show');
            }

            // Second check: parental authorization for underage users
            if ($user->needsParentalAuthorization()) {
                $this->rememberIntended($request);
                return redirect()->route('compliance.parental.show');
            }

            // Mark as compliant in session to avoid future checks
            session()->put($sessionKey, true);
        }

        return $next($request);
    }

    /**
     * Store the URL to return to after compliance. An XHR fragment URL is never
     * stored (it would render as raw HTML); only a same-host Referer is trusted.
     */
    private function rememberIntended(Request $request): void
    {
        if ($request->routeIs('compliance.*')) {
            return;
        }

        if (!$request->ajax()) {
            session()->put('url.intended', $request->fullUrl());
            return;
        }

        $referer = $request->headers->get('referer');
        if ($referer && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            session()->put('url.intended', $referer);
        }
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
