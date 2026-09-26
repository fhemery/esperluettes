<?php

namespace App\Domains\Config\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Feature-toggle rows for the E2E environment (see .env.e2e).
 * Mirrored in `e2e/support/fixtures.ts`.
 *
 * One orphan row — stored, but declared by no service provider — so the
 * admin page has something to show in its « Non déclarés dans le code »
 * section. Declared toggles get no row: they must show as OFF without one.
 */
class E2eFeatureTogglesSeeder extends Seeder
{
    public const ORPHAN_DOMAIN = 'moderation';
    public const ORPHAN_NAME = 'reporting';

    public function run(): void
    {
        DB::table('config_feature_toggles')->updateOrInsert(
            ['domain' => self::ORPHAN_DOMAIN, 'name' => self::ORPHAN_NAME],
            ['access' => 'on', 'roles' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        );
    }
}
