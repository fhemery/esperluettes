<?php

namespace App\Domains\Shared\Http\Middleware;

use App\Domains\Shared\Services\AppearanceService;
use App\Domains\Shared\Services\FontService;
use App\Domains\Shared\Services\InterlineService;
use App\Domains\Shared\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveThemeMiddleware
{
    public function __construct(
        private readonly ThemeService $themeService,
        private readonly AppearanceService $appearanceService,
        private readonly FontService $fontService,
        private readonly InterlineService $interlineService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $theme = $this->themeService->current();
        $appearance = $this->appearanceService->current();
        $font = $this->fontService->current();
        $interline = $this->interlineService->current();

        // Share theme, appearance, font and interline with all views
        View::share('theme', $theme);
        View::share('appearance', $appearance);
        View::share('userFont', $font);
        View::share('userInterline', $interline);

        // Also store in request attributes for controller access
        $request->attributes->set('theme', $theme);
        $request->attributes->set('appearance', $appearance);
        $request->attributes->set('userFont', $font);
        $request->attributes->set('userInterline', $interline);

        return $next($request);
    }
}
