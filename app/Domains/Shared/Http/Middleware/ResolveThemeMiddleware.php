<?php

namespace App\Domains\Shared\Http\Middleware;

use App\Domains\Shared\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveThemeMiddleware
{
    public function __construct(
        private readonly ThemeService $themeService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $theme = $this->themeService->current();

        // Share theme with all views
        View::share('theme', $theme);

        // Also store in request attributes for controller access
        $request->attributes->set('theme', $theme);

        return $next($request);
    }
}
