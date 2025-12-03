<?php

namespace App\Domains\Config\Public\Contracts;

enum ConfigParameterVisibility: string
{
    case TECH_ADMINS_ONLY = 'tech_admins_only';
    case ALL_ADMINS = 'all_admins';
}
