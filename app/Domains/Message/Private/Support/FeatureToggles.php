<?php

namespace App\Domains\Message\Private\Support;

class FeatureToggles
{
    public const DomainName = 'message';

    public const ActiveFeatureName = 'active';

    public static function getActiveFeatureName(): string
    {
        return self::DomainName . self::ActiveFeatureName;
    }
}