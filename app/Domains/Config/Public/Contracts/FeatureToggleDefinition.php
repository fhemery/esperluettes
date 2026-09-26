<?php

namespace App\Domains\Config\Public\Contracts;

/**
 * Immutable declaration of a feature toggle.
 * Registered by domains in their ServiceProvider boot().
 */
final class FeatureToggleDefinition
{
    public function __construct(
        public readonly string $domain,
        public readonly string $name,
        public readonly FeatureToggleAdminVisibility $adminVisibility = FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
    ) {}
}
