<?php

namespace App\Domains\Story\Database\Seeders;

use App\Domains\Auth\Database\Seeders\E2eAccountsSeeder;
use App\Domains\Editor\Public\Api\EditorPublicApi;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Models\StoryRefCopyright;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\Story\Private\Services\ChapterCreditService;
use App\Domains\Story\Private\Services\CollaboratorService;
use App\Domains\StoryRef\Private\Models\StoryRefType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Two public stories for the E2E environment (see .env.e2e), mirrored in
 * `e2e/support/fixtures.ts`.
 *
 * Story 1 is the author's own: a published chapter, a draft, and — for the
 * MultiEdit work — a matched pair of chapters carrying the *same six
 * paragraphs*, one in Simple mode and one in Advanced mode, so a spec can
 * compare their typography paragraph by paragraph, plus a chapter used only to
 * prove a no-op conversion moves no word count.
 *
 * Story 2 is co-authored by the `author` and `confirmed` accounts, which is the
 * only way to exercise "a co-author converts a chapter and uploads into their
 * own media folder". `confirmed` stays a plain reader on story 1.
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

    public const SIMPLE_CHAPTER_ID = 3;
    public const SIMPLE_CHAPTER_SLUG = 'chapitre-simple-3';

    public const ADVANCED_CHAPTER_ID = 4;
    public const ADVANCED_CHAPTER_SLUG = 'chapitre-avance-4';

    public const COUNTED_CHAPTER_ID = 5;
    public const COUNTED_CHAPTER_SLUG = 'chapitre-compte-5';

    public const COAUTHORED_STORY_ID = 2;
    public const COAUTHORED_STORY_SLUG = 'histoire-coecrite-2';
    public const COAUTHORED_STORY_TITLE = 'Histoire coécrite E2E';

    public const COAUTHORED_CHAPTER_ID = 6;
    public const COAUTHORED_CHAPTER_SLUG = 'chapitre-coecrit-6';

    public const ILLUSTRATED_CHAPTER_ID = 7;
    public const ILLUSTRATED_CHAPTER_SLUG = 'chapitre-illustre-7';

    /** Fixture image of the illustrated chapter, copied from the repo on seed. */
    public const ILLUSTRATION_PATH = 'chapters/e2e/illustration.jpg';

    /**
     * The six paragraphs shared by the Simple and the Advanced chapter. The
     * Advanced one splits them 2/2/2 across three text blocks, so a spec can
     * measure spacing *inside* a block and *across* a block boundary.
     */
    private const PARAGRAPHS = [
        'Alpha un. La première phrase du premier bloc, assez longue pour occuper une ligne entière.',
        'Alpha deux. La seconde phrase du premier bloc, elle aussi suffisamment longue.',
        'Beta un. La première phrase du deuxième bloc, à comparer avec la précédente.',
        'Beta deux. La seconde phrase du deuxième bloc, toujours de la même longueur.',
        'Gamma un. La première phrase du troisième bloc, pour finir la comparaison.',
        'Gamma deux. La toute dernière phrase du chapitre, qui ne doit pas avoir de marge basse.',
    ];

    /**
     * The illustrated chapter: inline formatting in the first block, then a
     * lazily-loaded image, then more prose. Quoted passages sit both inside the
     * formatting and *below* the image, which is what makes it possible to see
     * whether the author heat's margin markers drift once the image arrives.
     */
    private const ILLUSTRATED_BLOCKS_TEXT = [
        '<p>Delta un. Un paragraphe avec de l\'<em>italique</em> et du <strong>gras</strong> au milieu de la phrase.</p>',
        '<p>Epsilon un. Le paragraphe qui suit l\'image et qui descend quand elle arrive.</p>',
    ];

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

        // Chapter credits are granted by a listener on user registration, which
        // the account seeder bypasses — without this the author has 0 credits
        // and every chapter creation is refused with a 403.
        app(ChapterCreditService::class)->grantInitialOnRegistration((int) $authorId);

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

        $this->createTypographyPair($story->id);
        $this->createIllustratedChapter($story->id);

        $this->createChapter(self::COUNTED_CHAPTER_ID, 'Chapitre compté', self::COUNTED_CHAPTER_SLUG, [
            'content' => '<p>Trois mots ici.</p><p>Et quatre mots là.</p>',
            'sort_order' => 5,
            'status' => Chapter::STATUS_PUBLISHED,
            'first_published_at' => now(),
        ], $story->id);

        $story->update(['last_chapter_published_at' => now()]);

        $this->createCoauthoredStory($authorId);
    }

    /**
     * The Simple / Advanced pair carrying identical prose. The rendered content
     * of the Advanced chapter goes through the real renderer, so the fixture
     * cannot drift from what a save would actually produce.
     */
    private function createTypographyPair(int $storyId): void
    {
        $paragraphs = array_map(fn (string $p) => '<p>' . $p . '</p>', self::PARAGRAPHS);

        $this->createChapter(self::SIMPLE_CHAPTER_ID, 'Chapitre simple', self::SIMPLE_CHAPTER_SLUG, [
            'content' => implode('', $paragraphs),
            'sort_order' => 3,
            'status' => Chapter::STATUS_PUBLISHED,
            'first_published_at' => now(),
        ], $storyId);

        $blocks = [
            ['type' => 'text', 'html' => $paragraphs[0] . $paragraphs[1]],
            ['type' => 'text', 'html' => $paragraphs[2] . $paragraphs[3]],
            ['type' => 'text', 'html' => $paragraphs[4] . $paragraphs[5]],
        ];

        $this->createChapter(self::ADVANCED_CHAPTER_ID, 'Chapitre avancé', self::ADVANCED_CHAPTER_SLUG, [
            'content' => app(EditorPublicApi::class)->render($blocks, 'multiedit-narrative'),
            'content_blocks' => $blocks,
            'sort_order' => 4,
            'status' => Chapter::STATUS_PUBLISHED,
            'first_published_at' => now(),
        ], $storyId);
    }

    /**
     * An Advanced chapter with a real image block between two text blocks. The
     * image is served raw (`keep_original`) so it has no width or height in the
     * markup and only settles on `load` — the reflow the author heat's markers
     * have to survive.
     */
    private function createIllustratedChapter(int $storyId): void
    {
        Storage::disk('public')->put(
            self::ILLUSTRATION_PATH,
            (string) file_get_contents(public_path('images/story/policier.jpg'))
        );

        $blocks = [
            ['type' => 'text', 'html' => self::ILLUSTRATED_BLOCKS_TEXT[0]],
            [
                'type' => 'image',
                'path' => self::ILLUSTRATION_PATH,
                'alt' => 'Illustration E2E',
                'keep_original' => true,
            ],
            ['type' => 'text', 'html' => self::ILLUSTRATED_BLOCKS_TEXT[1]],
        ];

        $this->createChapter(self::ILLUSTRATED_CHAPTER_ID, 'Chapitre illustré', self::ILLUSTRATED_CHAPTER_SLUG, [
            'content' => app(EditorPublicApi::class)->render($blocks, 'multiedit-narrative'),
            'content_blocks' => $blocks,
            'sort_order' => 6,
            'status' => Chapter::STATUS_PUBLISHED,
            'first_published_at' => now(),
        ], $storyId);
    }

    private function createCoauthoredStory(int $authorId): void
    {
        $coAuthorId = DB::table('users')
            ->where('email', E2eAccountsSeeder::CONFIRMED_EMAIL)
            ->value('id');

        if (!$coAuthorId) {
            $this->command?->warn('E2e confirmed account missing; skipping the co-authored story.');
            return;
        }

        $story = new Story([
            'created_by_user_id' => $authorId,
            'title' => self::COAUTHORED_STORY_TITLE,
            'slug' => self::COAUTHORED_STORY_SLUG,
            'description' => '<p>Une histoire écrite à deux mains.</p>',
            'visibility' => Story::VIS_PUBLIC,
            'tw_disclosure' => Story::TW_NO_TW,
            'story_ref_type_id' => StoryRefType::value('id'),
            'story_ref_audience_id' => StoryRefAudience::value('id'),
            'story_ref_copyright_id' => StoryRefCopyright::value('id'),
        ]);
        $story->id = self::COAUTHORED_STORY_ID;
        $story->save();

        DB::table('story_genres')->insert([
            'story_id' => $story->id,
            'story_ref_genre_id' => StoryRefGenre::value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$authorId, $coAuthorId] as $userId) {
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $userId,
                'role' => 'author',
                'invited_by_user_id' => $authorId,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);
        }

        // A beta reader, so a spec can prove that being a collaborator is not
        // enough to see the author view — only `role = 'author'` is.
        $betaId = DB::table('users')
            ->where('email', E2eAccountsSeeder::MODERATOR_EMAIL)
            ->value('id');

        if ($betaId) {
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $betaId,
                'role' => CollaboratorService::ROLE_BETA_READER,
                'invited_by_user_id' => $authorId,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);
        }

        $this->createChapter(self::COAUTHORED_CHAPTER_ID, 'Chapitre coécrit', self::COAUTHORED_CHAPTER_SLUG, [
            'content' => '<p>Un chapitre que deux personnes peuvent modifier.</p>',
            'sort_order' => 1,
            'status' => Chapter::STATUS_PUBLISHED,
            'first_published_at' => now(),
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
