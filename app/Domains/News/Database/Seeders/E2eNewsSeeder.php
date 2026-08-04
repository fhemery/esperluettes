<?php

namespace App\Domains\News\Database\Seeders;

use App\Domains\Auth\Database\Seeders\E2eAccountsSeeder;
use App\Domains\News\Private\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * News items for the E2E environment (see .env.e2e).
 * Mirrored in `e2e/support/fixtures.ts`.
 *
 * Three articles, because the comment thread behaves differently per status:
 * the published one carries the thread, the draft one must carry none, and the
 * disposable one exists to be deleted with its thread from the admin panel.
 */
class E2eNewsSeeder extends Seeder
{
    public const SLUG = 'actualite-e2e';
    public const TITLE = 'Actualité E2E';

    public const DRAFT_SLUG = 'actualite-e2e-brouillon';
    public const DRAFT_TITLE = 'Actualité E2E brouillon';

    public const DISPOSABLE_SLUG = 'actualite-e2e-a-supprimer';
    public const DISPOSABLE_TITLE = 'Actualité E2E à supprimer';

    public function run(): void
    {
        $adminId = DB::table('users')->where('email', E2eAccountsSeeder::ADMIN_EMAIL)->value('id');

        News::firstOrCreate(
            ['slug' => self::SLUG],
            [
                'title' => self::TITLE,
                'summary' => 'Une actualité de test.',
                'content' => '<p>Le corps de l\'actualité.</p>',
                'status' => 'published',
                'published_at' => now(),
                'created_by' => $adminId,
            ]
        );

        News::firstOrCreate(
            ['slug' => self::DRAFT_SLUG],
            [
                'title' => self::DRAFT_TITLE,
                'summary' => 'Une actualité de test non publiée.',
                'content' => '<p>Le corps du brouillon.</p>',
                'status' => 'draft',
                'published_at' => null,
                'created_by' => $adminId,
            ]
        );

        News::firstOrCreate(
            ['slug' => self::DISPOSABLE_SLUG],
            [
                'title' => self::DISPOSABLE_TITLE,
                'summary' => 'Une actualité de test vouée à la suppression.',
                'content' => '<p>Le corps de l\'actualité à supprimer.</p>',
                'status' => 'published',
                'published_at' => now(),
                'created_by' => $adminId,
            ]
        );
    }
}
