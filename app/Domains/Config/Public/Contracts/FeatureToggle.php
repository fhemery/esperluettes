<?php

namespace App\Domains\Config\Public\Contracts;

/**
 * FeatureToggle contract
 */
class FeatureToggle {
    /**
     * Constructor
     *
     * @param string $name
     * @param string $domain
     * @param FeatureToggleAdminVisibility $admin_visibility
     * @param FeatureToggleAccess $access
     * @param array $roles
     */
    public function __construct(
        public string $name,
        public string $domain,
        public FeatureToggleAdminVisibility $admin_visibility = FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
        public FeatureToggleAccess $access = FeatureToggleAccess::OFF,
        public array $roles = [],
    ) {}
}
