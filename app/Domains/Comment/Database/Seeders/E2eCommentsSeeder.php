<?php

namespace App\Domains\Comment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One root comment on the published E2E chapter for Playwright specs, mirrored
 * in `e2e/support/fixtures.ts`.
 *
 * Chapter and user ids are the ones `E2eStorySeeder` and `E2eAccountsSeeder`
 * pin; they are repeated here as literals rather than imported, because a
 * domain does not reach into another domain's classes.
 *
 * Inserted via `DB::table` so no `CommentPosted` side effects run (credits,
 * author notifications). Root bodies need 140 plain-text chars through the
 * API; direct insert sidesteps that for a short fixture marker.
 */
class E2eCommentsSeeder extends Seeder
{
    /** Matches `E2eStorySeeder::CHAPTER_ID` / `chapitre-publie-1`. */
    private const CHAPTER_ID = 1;

    public const ROOT_COMMENT_ID = 1;

    public const BODY_MARKER = 'Commentaire E2E pour éditeur';

    /**
     * One root comment each for the promotable account
     * (`E2eAccountsSeeder::PROMOTABLE_EMAIL`). Promotion counts *distinct*
     * chapters, and five is the default threshold. Chapters 3–7 of
     * `E2eStorySeeder` — chapter 1 is left alone, the comment specs read it.
     */
    private const PROMOTABLE_CHAPTER_IDS = [3, 4, 5, 6, 7];

    public function run(): void
    {
        if (DB::table('comments')->exists()) {
            return;
        }

        $authorId = DB::table('users')
            ->where('email', 'confirmed@e2e.test')
            ->value('id');

        if ($authorId === null) {
            $this->command?->warn('E2e accounts missing. Run E2eAccountsSeeder first.');
            return;
        }

        DB::table('comments')->insert([
            'id' => self::ROOT_COMMENT_ID,
            'commentable_type' => 'chapter',
            'commentable_id' => self::CHAPTER_ID,
            'author_id' => (int) $authorId,
            'parent_comment_id' => null,
            'is_active' => true,
            'body' => '<p>'.self::BODY_MARKER.'</p>',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $promotableId = DB::table('users')
            ->where('email', 'promotable@e2e.test')
            ->value('id');

        if ($promotableId === null) {
            return;
        }

        foreach (self::PROMOTABLE_CHAPTER_IDS as $chapterId) {
            DB::table('comments')->insert([
                'commentable_type' => 'chapter',
                'commentable_id' => $chapterId,
                'author_id' => (int) $promotableId,
                'parent_comment_id' => null,
                'is_active' => true,
                'body' => '<p>Commentaire de promotion E2E</p>',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]);
        }
    }
}
