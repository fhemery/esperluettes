<?php

namespace App\Domains\StaticPage\Database\Seeders;

use App\Domains\Auth\Database\Seeders\E2eAccountsSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One published simple static page for the E2E environment (see .env.e2e).
 * Mirrored in `e2e/support/fixtures.ts`.
 *
 * Inserted via the query builder (not Eloquent) so the slug-map observer does
 * not need a writable file cache during `migrate:fresh --seed`. Admin edit
 * URLs use the pinned id; public catch-all resolution is out of scope for the
 * MultiEdit VERIFY specs.
 */
class E2eStaticPageSeeder extends Seeder
{
    /** Pinned so admin edit URLs in fixtures stay stable across `migrate:fresh`. */
    public const ID = 1;

    public const SLUG = 'page-e2e';
    public const TITLE = 'Page E2E';
    public const BODY = 'Corps simple de la page E2E.';

    public function run(): void
    {
        $adminId = DB::table('users')->where('email', E2eAccountsSeeder::ADMIN_EMAIL)->value('id');
        $now = now();

        DB::table('static_pages')->updateOrInsert(
            ['id' => self::ID],
            [
                'title' => self::TITLE,
                'slug' => self::SLUG,
                'summary' => 'Résumé de la page E2E.',
                'content' => '<p>' . self::BODY . '</p>',
                'content_blocks' => null,
                'status' => 'published',
                'published_at' => $now,
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
