<?php

namespace App\Domains\Config\Public\Contracts;


class FeatureToggle {
 public function __construct(
    public string $name,
    public string $domain,
    public FeatureToggleAdminVisibility $admin_visibility = FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
    public FeatureToggleAccess $access = FeatureToggleAccess::OFF,
    public array $roles = [],
 ) {}
}

enum FeatureToggleAccess: string {
    case OFF = 'off';
    case ON = 'on';
    case ROLE_BASED = 'role_based';
}

enum FeatureToggleAdminVisibility: string {
    case TECH_ADMINS_ONLY = 'tech_admins_only';
    case ALL_ADMINS = 'all_admins';
}
