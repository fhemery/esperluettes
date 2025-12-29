<?php

namespace App\Domains\Shared\Http\Middleware;

use App\Domains\Shared\Services\FontService;
use App\Domains\Shared\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveThemeMiddleware
{
    public function __construct(
        private readonly ThemeService $themeService,
        private readonly FontService $fontService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $theme = $this->themeService->current();
        $font = $this->fontService->current();

        // Share theme and font with all views
        View::share('theme', $theme);
        View::share('userFont', $font);

        // Also store in request attributes for controller access
        $request->attributes->set('theme', $theme);
        $request->attributes->set('userFont', $font);

        return $next($request);
    }
}
