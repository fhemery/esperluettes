<?php

namespace App\Domains\Shared\Helpers;

use Illuminate\Support\Facades\Cache;

class VersionHelper
{
    public static function get(): ?string
    {
        return Cache::remember('app_version', 3600, function () {
            $path = base_path('versions.json');
            if (! file_exists($path)) {
                return null;
            }

            $data = json_decode(file_get_contents($path), true);
            $version = $data['version'] ?? null;

            if (!$version || $version === 'unknown') {
                return null;
            }

            return $version;
        });
    }
}
