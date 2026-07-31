<?php

namespace App\Domains\Story\Database\Seeders;

use App\Domains\Auth\Database\Seeders\E2eAccountsSeeder;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Models\StoryRefCopyright;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One public story with a published and a draft chapter, for the E2E
 * environment (see .env.e2e). Mirrored in `e2e/support/fixtures.ts`.
 *
 * Stories and chapters store slug-with-id ('mon-histoire-1') and the app 301s
 * anything else to that exact string, so the slug is written with the id
 * already in it. The ids are pinned rather than left to the sequence: the
 * specs hard-code these URLs, and "it happened to be 1" is not a promise.
 * `migrate:fresh` before every run makes pinning safe.
 */
class E2eStorySeeder extends Seeder
{
    public const STORY_ID = 1;
    public const STORY_SLUG = 'histoire-e2e-1';
    public const STORY_TITLE = 'Histoire E2E';

    public const CHAPTER_ID = 1;
    public const CHAPTER_SLUG = 'chapitre-publie-1';

    public const DRAFT_CHAPTER_ID = 2;
    public const DRAFT_CHAPTER_SLUG = 'chapitre-brouillon-2';

    public function run(): void
    {
        if (Story::where('slug', self::STORY_SLUG)->exists()) {
            return;
        }

        $authorId = DB::table('users')
            ->where('email', E2eAccountsSeeder::AUTHOR_EMAIL)
            ->value('id');

        if (!$authorId) {
            $this->command?->warn('E2e author account missing. Run E2eAccountsSeeder first.');
            return;
        }

        $story = new Story([
            'created_by_user_id' => $authorId,
            'title' => self::STORY_TITLE,
            'slug' => self::STORY_SLUG,
            'description' => '<p>Une histoire de test, stable entre deux exécutions.</p>',
            'visibility' => Story::VIS_PUBLIC,
            'tw_disclosure' => Story::TW_NO_TW,
            'story_ref_type_id' => StoryRefType::value('id'),
            'story_ref_audience_id' => StoryRefAudience::value('id'),
            'story_ref_copyright_id' => StoryRefCopyright::value('id'),
        ]);
        $story->id = self::STORY_ID;
        $story->save();

        DB::table('story_genres')->insert([
            'story_id' => $story->id,
            'story_ref_genre_id' => StoryRefGenre::value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('story_collaborators')->insert([
            'story_id' => $story->id,
            'user_id' => $authorId,
            'role' => 'author',
            'invited_by_user_id' => $authorId,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->createChapter(self::CHAPTER_ID, 'Chapitre publié', self::CHAPTER_SLUG, [
            'content' => '<p>Le contenu du premier chapitre.</p>',
            'sort_order' => 1,
            'status' => Chapter::STATUS_PUBLISHED,
            'first_published_at' => now(),
        ], $story->id);

        $this->createChapter(self::DRAFT_CHAPTER_ID, 'Chapitre brouillon', self::DRAFT_CHAPTER_SLUG, [
            'content' => '<p>Pas encore publié.</p>',
            'sort_order' => 2,
            'status' => Chapter::STATUS_NOT_PUBLISHED,
        ], $story->id);

        $story->update(['last_chapter_published_at' => now()]);
    }

    private function createChapter(int $id, string $title, string $slug, array $attributes, int $storyId): void
    {
        $chapter = new Chapter(array_merge([
            'story_id' => $storyId,
            'title' => $title,
            'slug' => $slug,
        ], $attributes));
        $chapter->id = $id;
        $chapter->save();
    }
}
