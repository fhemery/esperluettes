<?php

namespace App\Domains\Shared\Services;

use App\Domains\Shared\Contracts\Theme;

class ThemeService
{
    /**
     * Resolve the theme for a given user.
     *
     * Phase 1: Always returns seasonal theme.
     * Phase 2 (after Settings domain): Will check user preference first.
     *
     * @param  int|null  $userId  User ID to check preference for (null = guest)
     */
    public function resolve(?int $userId = null): Theme
    {
        // TODO: When Settings domain is implemented, check user preference:
        // if ($userId) {
        //     $pref = app(SettingsPublicApi::class)->getValue($userId, 'appearance', 'theme');
        //     if ($pref && $pref !== 'seasonal') {
        //         return Theme::tryFrom($pref) ?? Theme::seasonal();
        //     }
        // }

        return Theme::seasonal();
    }

    /**
     * Get the current theme for the authenticated user (or seasonal for guests).
     */
    public function current(): Theme
    {
        return $this->resolve(auth()->id());
    }
}
