<?php

namespace App\Domains\News\Database\Seeders;

use App\Domains\Auth\Database\Seeders\E2eAccountsSeeder;
use App\Domains\News\Private\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One published news item for the E2E environment (see .env.e2e).
 * Mirrored in `e2e/support/fixtures.ts`.
 */
class E2eNewsSeeder extends Seeder
{
    public const SLUG = 'actualite-e2e';
    public const TITLE = 'Actualité E2E';

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
    }
}
