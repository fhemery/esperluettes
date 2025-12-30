<?php

namespace App\Domains\Shared\Services;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Contracts\Theme;
use App\Domains\Shared\Providers\SharedServiceProvider;

class ThemeService
{
    public function __construct(
        private SettingsPublicApi $settingsApi,
    ) {}

    /**
     * Resolve the theme for a given user.
     *
     * @param  int|null  $userId  User ID to check preference for (null = guest)
     */
    public function resolve(?int $userId = null): Theme
    {
        if ($userId) {
            $pref = $this->settingsApi->getValue(
                $userId,
                SharedServiceProvider::TAB_GENERAL,
                SharedServiceProvider::KEY_THEME
            );

            if ($pref && $pref !== 'seasonal') {
                return Theme::tryFrom($pref) ?? Theme::seasonal();
            }
        }

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
