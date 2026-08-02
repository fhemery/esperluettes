<?php

namespace App\Domains\Quote\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reader quotes for the E2E environment (see .env.e2e), mirrored in
 * `e2e/support/fixtures.ts`.
 *
 * They exist for the author view: the heat map, the margin markers, the passage
 * popover and the chapter summary all need several readers quoting overlapping,
 * repeated and vanished passages — none of which a spec may create for itself,
 * since the author cannot quote their own story.
 *
 * Chapter and story ids are the ones `E2eStorySeeder` pins; they are repeated
 * here as literals rather than imported, because a domain does not reach into
 * another domain's classes.
 *
 * `prefix` / `suffix` are left null wherever the highlighted text already occurs
 * exactly once in the chapter: `findAnchor()` then matches on the text alone. A
 * hand-written context that does not match the five words the reader-side
 * extractor would have stored turns the row stale and the fixture silently
 * useless. Two rows carry a real context anyway, so the triple-match path is
 * exercised too.
 */
class E2eQuotesSeeder extends Seeder
{
    private const STORY_ID = 1;
    private const COAUTHORED_STORY_ID = 2;

    private const DRAFT_CHAPTER_ID = 2;
    private const SIMPLE_CHAPTER_ID = 3;
    private const COAUTHORED_CHAPTER_ID = 6;
    private const ILLUSTRATED_CHAPTER_ID = 7;

    /** The passage two readers quoted identically — one summary row, count 2. */
    public const SHARED_PASSAGE = 'La première phrase du premier bloc,';

    /** Overlaps the tail of SHARED_PASSAGE, so the tint reaches depth 3. */
    public const OVERLAPPING_PASSAGE = 'du premier bloc, assez longue';

    /** Quoted once, far from the others. */
    public const LONE_PASSAGE = 'à comparer avec la précédente';

    /** No longer in the chapter: counted by the badge, absent from the heat. */
    public const STALE_PASSAGE = 'Un passage qui n\'existe plus dans ce chapitre';

    /** Spans <em> and <strong> inside a single paragraph — must tint in one go. */
    public const FORMATTED_PASSAGE = 'de l\'italique et du gras';

    /** Sits below the illustrated chapter's lazily-loaded image. */
    public const BELOW_IMAGE_PASSAGE = 'qui suit l\'image';

    public function run(): void
    {
        if (DB::table('quotes')->exists()) {
            return;
        }

        $users = DB::table('users')
            ->whereIn('email', ['confirmed@e2e.test', 'admin@e2e.test', 'moderator@e2e.test'])
            ->pluck('id', 'email');

        if ($users->count() < 3) {
            $this->command?->warn('E2e accounts missing. Run E2eAccountsSeeder first.');
            return;
        }

        $confirmed = (int) $users['confirmed@e2e.test'];
        $admin = (int) $users['admin@e2e.test'];
        $moderator = (int) $users['moderator@e2e.test'];

        $rows = [
            // The simple chapter carries the whole heat scenario.
            [$confirmed, self::SIMPLE_CHAPTER_ID, self::STORY_ID, self::SHARED_PASSAGE,
                'Alpha un.', 'assez longue pour occuper une', 'Ma note privée de lecteur.', 5],
            [$admin, self::SIMPLE_CHAPTER_ID, self::STORY_ID, self::SHARED_PASSAGE,
                'Alpha un.', 'assez longue pour occuper une', 'Une autre note privée.', 3],
            [$moderator, self::SIMPLE_CHAPTER_ID, self::STORY_ID, self::OVERLAPPING_PASSAGE,
                null, null, 'Note du chevauchement.', 1],
            [$confirmed, self::SIMPLE_CHAPTER_ID, self::STORY_ID, self::LONE_PASSAGE,
                null, null, null, 2],
            [$admin, self::SIMPLE_CHAPTER_ID, self::STORY_ID, self::STALE_PASSAGE,
                null, null, 'Sur un passage réécrit depuis.', 4],

            // The author's own draft: the view must work before publication.
            [$confirmed, self::DRAFT_CHAPTER_ID, self::STORY_ID, 'encore publié', null, null, null, 1],

            // The co-authored story, seen by its second author.
            [$moderator, self::COAUTHORED_CHAPTER_ID, self::COAUTHORED_STORY_ID,
                'deux personnes', null, null, null, 1],

            // The illustrated chapter: one quote across inline formatting, one
            // below the image whose late load moves every line under it.
            [$confirmed, self::ILLUSTRATED_CHAPTER_ID, self::STORY_ID, self::FORMATTED_PASSAGE,
                null, null, null, 6],
            [$admin, self::ILLUSTRATED_CHAPTER_ID, self::STORY_ID, self::BELOW_IMAGE_PASSAGE,
                null, null, null, 1],
        ];

        foreach ($rows as [$userId, $chapterId, $storyId, $text, $prefix, $suffix, $note, $daysAgo]) {
            DB::table('quotes')->insert([
                'user_id' => $userId,
                'chapter_id' => $chapterId,
                'story_id' => $storyId,
                'highlighted_text' => $text,
                'prefix' => $prefix,
                'suffix' => $suffix,
                'note' => $note,
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ]);
        }
    }
}
