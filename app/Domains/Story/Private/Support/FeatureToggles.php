<?php

namespace App\Domains\Story\Private\Support;

use App\Domains\Config\Public\Api\ConfigPublicApi;

class FeatureToggles
{
    public const DOMAIN = 'story';

    public const THEME_COVERS_ENABLED = 'theme_covers_enabled';
    public const CUSTOM_COVERS_ENABLED = 'custom_covers_enabled';

    public static function isCoverSelectionEnabled(): bool
    {
        return self::isThemeCoverEnabled() || self::isCustomCoverEnabled();
    }

    public static function isThemeCoverEnabled(): bool
    {
        return (bool) app(ConfigPublicApi::class)->getParameterValue(self::THEME_COVERS_ENABLED, self::DOMAIN);
    }

    public static function isCustomCoverEnabled(): bool
    {
        return (bool) app(ConfigPublicApi::class)->getParameterValue(self::CUSTOM_COVERS_ENABLED, self::DOMAIN);
    }
}
