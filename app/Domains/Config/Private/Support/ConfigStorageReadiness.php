<?php

namespace App\Domains\Config\Private\Support;

use Illuminate\Support\Facades\Schema;

class ConfigStorageReadiness
{
    /**
     * Whether config persistence tables can be read.
     *
     * Returns false when the database is unreachable or migrations have not run yet
     * (e.g. during composer install / package:discover).
     */
    public static function isAvailable(): bool
    {
        try {
            return Schema::hasTable('config_feature_toggles');
        } catch (\Throwable) {
            return false;
        }
    }
}
