<?php

namespace App\Domains\Config\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Feature toggles the E2E world needs switched on.
 *
 * `discord/discord_notifications` makes the Discord column appear on the
 * notification preferences tab, so specs can see its default state and its
 * "account not linked" warning. The toggle name is repeated here as a literal
 * rather than imported: a domain does not reach into another domain's classes.
 *
 * Inserted directly because `ConfigPublicApi::addFeatureToggle` requires a
 * logged-in tech-admin. The toggle list is cached, so the cache entry is
 * dropped afterwards (same key as `FeatureToggleService::allCacheKey`).
 */
class E2eFeatureTogglesSeeder extends Seeder
{
    private const TOGGLES = [
        ['domain' => 'discord', 'name' => 'discord_notifications'],
    ];

    public function run(): void
    {
        foreach (self::TOGGLES as $toggle) {
            DB::table('config_feature_toggles')->updateOrInsert(
                ['domain' => $toggle['domain'], 'name' => $toggle['name']],
                [
                    'access' => 'on',
                    'admin_visibility' => 'tech_admins_only',
                    'roles' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        Cache::forget('feature_toggles:all');
    }
}
