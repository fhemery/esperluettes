<?php

use Illuminate\Support\Facades\Cache;

if (! function_exists('getAppVersion')) {
    function getAppVersion(): string
    {
        return Cache::rememberForever('app_version', function () {
            $path = base_path('versions.json');
            if (!file_exists($path)) return 'unknown';

            $json = json_decode(file_get_contents($path), true);
            return $json['version'] ?? 'unknown';
        });
    }
}
