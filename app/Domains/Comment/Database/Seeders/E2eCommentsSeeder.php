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

    /** Story 6 of `E2eStorySeeder`: the annotated chapter and the one under review. */
    private const ANNOTATED_CHAPTER_ID = 11;

    private const REVIEWED_CHAPTER_ID = 13;

    /** Pinned so specs can address each root's « N annotations » button. */
    public const ANNOTATED_CONFIRMED_ROOT_ID = 101;

    public const ANNOTATED_USER_ROOT_ID = 102;

    public const ANNOTATED_ANONYMISED_ROOT_ID = 103;

    public const ANNOTATED_DEACTIVATED_ROOT_ID = 104;

    public const REVIEWED_USER_ROOT_ID = 105;

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

        if ($promotableId !== null) {
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

        $this->seedInChapterAnnotations();
    }

    /**
     * The in-chapter annotation world, mirrored in `e2e/support/fixtures.ts`
     * (`ANNOTATED`). Anchors carry no prefix / suffix where the passage occurs
     * once, so `findAnchor()` matches on the text alone; the `moved` row is the
     * only one that relies on its context.
     *
     * - `confirmed` (101): a three-annotation line with `user`, a processed one,
     *   a missing one, a moved one, an emptied one, one under their own quote;
     *   the first carries an author reply and a deactivated writer's reply.
     * - `user` (102): one in the shared line, one beside the anonymised one.
     * - anonymised (103, `author_id` null, as `RemoveAuthorOnUserDeleted` leaves it).
     * - deactivated (104): soft-deleted, as `SoftDeleteCommentsOnUserDeactivated` leaves it.
     * - chapter 13, `user` (105): three annotations, the first answered by the author.
     */
    private function seedInChapterAnnotations(): void
    {
        $ids = DB::table('users')
            ->whereIn('email', ['author@e2e.test', 'confirmed@e2e.test', 'user@e2e.test', 'deactivated@e2e.test'])
            ->pluck('id', 'email')
            ->map(fn ($id) => (int) $id);

        if ($ids->count() < 4) {
            $this->command?->warn('E2e accounts missing; skipping the in-chapter annotations.');
            return;
        }

        $root = function (int $id, int $chapterId, ?int $authorId, ?string $deletedAt = null): void {
            DB::table('comments')->insert([
                'id' => $id,
                'commentable_type' => 'chapter',
                'commentable_id' => $chapterId,
                'author_id' => $authorId,
                'parent_comment_id' => null,
                'is_active' => true,
                'body' => '<p>Commentaire racine E2E pour les annotations.</p>',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
                'deleted_at' => $deletedAt,
            ]);
        };
        $annotation = function (int $rootId, ?int $authorId, string $body, ?string $text, array $extra = []): int {
            return (int) DB::table('comment_annotations')->insertGetId(array_merge([
                'comment_id' => $rootId,
                'parent_annotation_id' => null,
                'author_id' => $authorId,
                'body' => $body,
                'highlighted_text' => $text,
                'prefix' => null,
                'suffix' => null,
                'is_processed' => false,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ], $extra));
        };

        $confirmed = $ids['confirmed@e2e.test'];
        $user = $ids['user@e2e.test'];
        $author = $ids['author@e2e.test'];
        $deactivated = $ids['deactivated@e2e.test'];

        $root(self::ANNOTATED_CONFIRMED_ROOT_ID, self::ANNOTATED_CHAPTER_ID, $confirmed);
        $root(self::ANNOTATED_USER_ROOT_ID, self::ANNOTATED_CHAPTER_ID, $user);
        $root(self::ANNOTATED_ANONYMISED_ROOT_ID, self::ANNOTATED_CHAPTER_ID, null);
        $root(self::ANNOTATED_DEACTIVATED_ROOT_ID, self::ANNOTATED_CHAPTER_ID, $deactivated, now()->subDay()->toDateTimeString());
        $root(self::REVIEWED_USER_ROOT_ID, self::REVIEWED_CHAPTER_ID, $user);

        // Reading order on chapter 11 is the insertion order below, root by root.
        $fox = $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $confirmed, '<p>Joli début, le renard.</p>', 'Le renard roux');
        $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $confirmed, '<p>Quelle heure exactement ?</p>', 'au matin');
        $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $confirmed, '<p>Belle image de la chouette.</p>', 'depuis la branche la plus haute', [
            'is_processed' => true,
            'processed_at' => now()->subDay(),
        ]);
        $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $confirmed, '<p>Ce passage a disparu.</p>', 'Un passage disparu du chapitre');
        $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $confirmed, '<p>La boulangère a changé.</p>', 'La vieille boulangère ouvre', [
            'prefix' => 'Thêta deux.',
            'suffix' => 'sa boutique',
        ]);
        $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $confirmed, e('<Annotation supprimée>'), "s'éveille et les volets", [
            'content_emptied_at' => now()->subDay(),
        ]);
        $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $confirmed, '<p>Le ruisseau, sous ma citation.</p>', 'ruisseau chante sous le pont');

        $annotation(self::ANNOTATED_USER_ROOT_ID, $user, '<p>La clairière est bien décrite.</p>', 'traverse la clairière');
        $annotation(self::ANNOTATED_USER_ROOT_ID, $user, '<p>Un moulin qui tourne à vide.</p>', 'le moulin tourne encore');

        $annotation(self::ANNOTATED_ANONYMISED_ROOT_ID, null, '<p>Remarque d\'un compte supprimé.</p>', 'Plus loin');

        $annotation(self::ANNOTATED_DEACTIVATED_ROOT_ID, $deactivated, '<p>Remarque d\'un compte désactivé.</p>', 'Thêta un');

        foreach ([[$author, '<p>Merci pour le renard.</p>'], [$deactivated, '<p>Réponse d\'un compte désactivé.</p>']] as [$writer, $body]) {
            $annotation(self::ANNOTATED_CONFIRMED_ROOT_ID, $writer, $body, null, ['parent_annotation_id' => $fox]);
        }

        $first = $annotation(self::REVIEWED_USER_ROOT_ID, $user, '<p>Première remarque.</p>', 'La première remarque');
        $annotation(self::REVIEWED_USER_ROOT_ID, $user, '<p>Deuxième remarque.</p>', 'ce passage précis');
        $annotation(self::REVIEWED_USER_ROOT_ID, $user, '<p>Troisième remarque.</p>', 'sera supprimée bientôt');
        $annotation(self::REVIEWED_USER_ROOT_ID, $author, '<p>Réponse de l\'autrice.</p>', null, ['parent_annotation_id' => $first]);
    }
}
