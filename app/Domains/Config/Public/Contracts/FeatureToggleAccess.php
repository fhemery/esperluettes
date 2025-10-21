<?php

namespace App\Domains\Config\Public\Contracts;

enum FeatureToggleAccess: string {
    case OFF = 'off';
    case ON = 'on';
    case ROLE_BASED = 'role_based';
}
