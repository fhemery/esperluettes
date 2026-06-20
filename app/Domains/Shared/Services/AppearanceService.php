<?php

namespace App\Domains\Shared\Services;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Providers\SharedServiceProvider;

class AppearanceService
{
    public const LIGHT = 'light';

    public const DARK = 'dark';

    public const DEFAULT = self::LIGHT;

    private const VALID_VALUES = [
        self::LIGHT,
        self::DARK,
    ];

    public function __construct(
        private SettingsPublicApi $settingsApi,
    ) {}

    /**
     * Resolve the appearance mode for a given user.
     *
     * @param  int|null  $userId  User ID to check preference for (null = guest)
     */
    public function resolve(?int $userId = null): string
    {
        if ($userId) {
            $appearancePref = $this->settingsApi->getValue(
                $userId,
                SharedServiceProvider::TAB_GENERAL,
                SharedServiceProvider::KEY_APPEARANCE
            );

            if ($appearancePref === self::DARK) {
                return self::DARK;
            }

            // Legacy: theme=dark stored before appearance was split out
            $themePref = $this->settingsApi->getValue(
                $userId,
                SharedServiceProvider::TAB_GENERAL,
                SharedServiceProvider::KEY_THEME
            );

            if ($themePref === 'dark') {
                return self::DARK;
            }
        }

        return self::DEFAULT;
    }

    /**
     * Get the current appearance for the authenticated user (or default for guests).
     */
    public function current(): string
    {
        return $this->resolve(auth()->id());
    }
}
