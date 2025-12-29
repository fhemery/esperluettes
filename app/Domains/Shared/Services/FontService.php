<?php

namespace App\Domains\Shared\Services;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Providers\SharedServiceProvider;

class FontService
{
    public const FONT_APTOS = 'aptos';
    public const FONT_TIMES = 'times';

    public const DEFAULT_FONT = self::FONT_APTOS;

    public function __construct(
        private SettingsPublicApi $settingsApi,
    ) {}

    /**
     * Resolve the font preference for a given user.
     *
     * @param  int|null  $userId  User ID to check preference for (null = guest)
     * @return string Font identifier (aptos, times)
     */
    public function resolve(?int $userId = null): string
    {
        if ($userId) {
            $pref = $this->settingsApi->getValue(
                $userId,
                SharedServiceProvider::TAB_GENERAL,
                SharedServiceProvider::KEY_FONT
            );

            if ($pref && in_array($pref, [self::FONT_APTOS, self::FONT_TIMES], true)) {
                return $pref;
            }
        }

        return self::DEFAULT_FONT;
    }

    /**
     * Get the current font for the authenticated user (or default for guests).
     */
    public function current(): string
    {
        return $this->resolve(auth()->id());
    }

    /**
     * Get the CSS font-family value for a given font identifier.
     */
    public function getCssFontFamily(string $font): string
    {
        return match ($font) {
            self::FONT_TIMES => '"Times New Roman", Times, serif',
            default => '"Aptos", sans-serif',
        };
    }
}
