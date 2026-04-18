<?php

namespace App\Domains\Shared\Services;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Providers\SharedServiceProvider;

class InterlineService
{
    public const INTERLINE_LOW = 'low';
    public const INTERLINE_MEDIUM = 'medium';
    public const INTERLINE_HIGH = 'high';

    public const DEFAULT_INTERLINE = self::INTERLINE_MEDIUM;

    private const VALID_VALUES = [
        self::INTERLINE_LOW,
        self::INTERLINE_MEDIUM,
        self::INTERLINE_HIGH,
    ];

    public function __construct(
        private SettingsPublicApi $settingsApi,
    ) {}

    /**
     * Resolve the interline preference for a given user.
     *
     * @param  int|null  $userId  User ID to check preference for (null = guest)
     * @return string Interline identifier (faible, moyen, eleve)
     */
    public function resolve(?int $userId = null): string
    {
        if ($userId) {
            $pref = $this->settingsApi->getValue(
                $userId,
                SharedServiceProvider::TAB_GENERAL,
                SharedServiceProvider::KEY_INTERLINE
            );

            if ($pref && in_array($pref, self::VALID_VALUES, true)) {
                return $pref;
            }
        }

        return self::DEFAULT_INTERLINE;
    }

    /**
     * Get the current interline for the authenticated user (or default for guests).
     */
    public function current(): string
    {
        return $this->resolve(auth()->id());
    }
}
