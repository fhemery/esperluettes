<?php

namespace App\Domains\Calendar\Database\Seeders;

use App\Domains\Auth\Database\Seeders\E2eAccountsSeeder;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\QuoteContestRegistration;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Five *Concours de citations* activities for the E2E environment (see
 * .env.e2e), one per phase, mirrored in `e2e/support/fixtures.ts`.
 *
 * A contest's phase is derived from the clock alone, so the only way to look at
 * all five is to have five contests whose dates straddle `now()`. They are
 * seeded rather than built through the UI because the interlude, the vote
 * period and the end are each a different point in a timeline no spec can
 * fast-forward.
 *
 * Entries are snapshots by design (functional §4.3.11), so the awkward rows —
 * a story renamed since, a chapter since deleted, a submitter since
 * deactivated — are written here directly. That is exactly the shape the
 * application would have left behind, and the only way to reach those screens
 * without a time machine.
 *
 * Story, chapter and quote ids are the ones `E2eStorySeeder` / `E2eQuotesSeeder`
 * pin; they are repeated as literals rather than imported, since a domain does
 * not reach into another domain's classes.
 */
class E2eCalendarSeeder extends Seeder
{
    public const BEFORE_SLUG = 'concours-citations-avant';
    public const SUBMISSIONS_SLUG = 'concours-citations-soumissions';
    public const INTERLUDE_SLUG = 'concours-citations-entre-deux';
    public const VOTING_SLUG = 'concours-citations-votes';
    public const ENDED_SLUG = 'concours-citations-termine';

    /** The category of the submissions contest that already holds a quote of `confirmed`. */
    public const FILLED_CATEGORY_TITLE = 'Meilleure ouverture';
    /** The category left empty on purpose, so a first submission can be made. */
    public const EMPTY_CATEGORY_TITLE = 'Plus belle métaphore';

    /** The passage `confirmed` has already entered in the submissions contest. */
    public const SITTING_PASSAGE = 'La première phrase du premier bloc,';

    /** A story renamed after the entry was written: old title, still-valid slug. */
    public const STALE_STORY_TITLE = 'Ancien titre de l\'histoire';
    /** A chapter deleted after the entry was written: the link must 404, not crash. */
    public const DEAD_CHAPTER_SLUG = 'chapitre-supprime-99';

    /** An entry whose submitter no longer exists at all. */
    private const VANISHED_USER_ID = 999001;

    private const STORY_ID = 1;
    private const STORY_SLUG = 'histoire-e2e-1';
    private const STORY_TITLE = 'Histoire E2E';
    private const SIMPLE_CHAPTER_ID = 3;
    private const SIMPLE_CHAPTER_SLUG = 'chapitre-simple-3';
    private const SIMPLE_CHAPTER_TITLE = 'Chapitre simple';

    /** ids of the quotes `E2eQuotesSeeder` writes first, in its own order. */
    private const SHARED_QUOTE_ID = 1;
    private const LONE_QUOTE_ID = 4;

    /** @var array<string, int> email => user id */
    private array $users = [];

    public function run(): void
    {
        if (Activity::where('slug', self::SUBMISSIONS_SLUG)->exists()) {
            return;
        }

        $this->users = DB::table('users')
            ->where('email', 'like', '%@e2e.test')
            ->pluck('id', 'email')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! isset($this->users[E2eAccountsSeeder::CONFIRMED_EMAIL])) {
            $this->command?->warn('E2e accounts missing. Run E2eAccountsSeeder first.');
            return;
        }

        $this->seedBeforeStart();
        $this->seedSubmissions();
        $this->seedInterlude();
        $this->seedVoting();
        $this->seedEnded();
    }

    private function seedBeforeStart(): void
    {
        $id = $this->activity(self::BEFORE_SLUG, 'Concours de citations — avant le départ', [
            'preview_starts_at' => now()->subDay(),
            'active_starts_at' => now()->addDays(2),
            'active_ends_at' => now()->addDays(30),
        ], [
            'submissions_end_at' => now()->addDays(12),
            'votes_start_at' => now()->addDays(15),
        ]);

        $this->category($id, 'Meilleure ouverture', 'La phrase qui donne envie de lire la suite.', 1);
        $this->category($id, 'Plus belle métaphore', 'Une image qui reste en tête.', 2);
    }

    private function seedSubmissions(): void
    {
        $id = $this->activity(self::SUBMISSIONS_SLUG, 'Concours de citations — soumissions ouvertes', [
            'preview_starts_at' => now()->subDays(3),
            'active_starts_at' => now()->subDay(),
            'active_ends_at' => now()->addDays(20),
        ], [
            'submissions_end_at' => now()->addDays(5),
            'votes_start_at' => now()->addDays(10),
        ]);

        $filled = $this->category($id, self::FILLED_CATEGORY_TITLE, 'La phrase qui donne envie de lire la suite.', 1);
        $this->category($id, self::EMPTY_CATEGORY_TITLE, 'Une image qui reste en tête.', 2);
        $this->category($id, 'Dialogue le plus drôle', 'Une réplique qui fait rire tout seul.', 3);

        // `confirmed` already sits in one category, so the replace and withdraw
        // affordances are on screen from the first load.
        $this->entry($filled, $this->users[E2eAccountsSeeder::CONFIRMED_EMAIL], [
            'quote_id' => self::SHARED_QUOTE_ID,
            'highlighted_text' => self::SITTING_PASSAGE,
        ]);
    }

    private function seedInterlude(): void
    {
        $id = $this->activity(self::INTERLUDE_SLUG, 'Concours de citations — entre-deux', [
            'preview_starts_at' => now()->subDays(12),
            'active_starts_at' => now()->subDays(10),
            'active_ends_at' => now()->addDays(10),
        ], [
            'submissions_end_at' => now()->subDay(),
            'votes_start_at' => now()->addDays(2),
        ]);

        $category = $this->category($id, 'Meilleure ouverture', 'La phrase qui donne envie de lire la suite.', 1);
        $this->category($id, 'Plus belle métaphore', 'Une image qui reste en tête.', 2);

        $this->entry($category, $this->users[E2eAccountsSeeder::CONFIRMED_EMAIL], [
            'quote_id' => self::LONE_QUOTE_ID,
            'highlighted_text' => 'à comparer avec la précédente',
        ]);
    }

    private function seedVoting(): void
    {
        $id = $this->activity(self::VOTING_SLUG, 'Concours de citations — votes ouverts', [
            'preview_starts_at' => now()->subDays(12),
            'active_starts_at' => now()->subDays(10),
            'active_ends_at' => now()->addDays(5),
        ], [
            'submissions_end_at' => now()->subDays(3),
            'votes_start_at' => now()->subDay(),
        ]);

        $opening = $this->category($id, self::FILLED_CATEGORY_TITLE, 'La phrase qui donne envie de lire la suite.', 1);
        $metaphor = $this->category($id, self::EMPTY_CATEGORY_TITLE, 'Une image qui reste en tête.', 2);
        $this->category($id, 'Catégorie sans soumission', 'Personne n\'y a rien mis.', 3);

        $mine = $this->entry($opening, $this->users[E2eAccountsSeeder::CONFIRMED_EMAIL], [
            'quote_id' => self::SHARED_QUOTE_ID,
            'highlighted_text' => self::SITTING_PASSAGE,
        ]);

        $others = $this->entry($opening, $this->users[E2eAccountsSeeder::ADMIN_EMAIL], [
            'quote_id' => self::LONE_QUOTE_ID,
            'highlighted_text' => 'à comparer avec la précédente',
        ]);

        // Renamed since: the entry keeps the old title, the slug still resolves.
        $stale = $this->entry($opening, $this->users[E2eAccountsSeeder::MODERATOR_EMAIL], [
            'quote_id' => 3,
            'highlighted_text' => 'du premier bloc, assez longue',
            'story_title' => self::STALE_STORY_TITLE,
        ]);

        // Source quote deleted *and* chapter deleted: the snapshot stands alone
        // and the chapter link leads nowhere.
        $this->entry($opening, $this->users[E2eAccountsSeeder::AUTHOR_EMAIL], [
            'quote_id' => 999999,
            'highlighted_text' => 'Un passage dont la citation d\'origine a été supprimée',
            'chapter_id' => 99,
            'chapter_title' => 'Chapitre supprimé',
            'chapter_slug' => self::DEAD_CHAPTER_SLUG,
        ]);

        // Two submitters moderation cannot name: one deactivated, one gone.
        $this->entry($metaphor, $this->users[E2eAccountsSeeder::DEACTIVATED_EMAIL], [
            'quote_id' => 8,
            'highlighted_text' => 'de l\'italique et du gras',
        ]);

        $this->entry($metaphor, self::VANISHED_USER_ID, [
            'quote_id' => 999998,
            'highlighted_text' => 'Un passage soumis par un compte disparu',
        ]);

        // A tally the Résultats tab can show, cast by everyone but `confirmed`,
        // whose own ballot the browser is there to cast.
        $this->vote($opening, $others, $this->users[E2eAccountsSeeder::ADMIN_EMAIL]);
        $this->vote($opening, $others, $this->users[E2eAccountsSeeder::MODERATOR_EMAIL]);
        $this->vote($opening, $mine, $this->users[E2eAccountsSeeder::AUTHOR_EMAIL]);
        $this->vote($opening, $stale, $this->users[E2eAccountsSeeder::DEACTIVATED_EMAIL]);
    }

    private function seedEnded(): void
    {
        $id = $this->activity(self::ENDED_SLUG, 'Concours de citations — terminé', [
            'preview_starts_at' => now()->subDays(30),
            'active_starts_at' => now()->subDays(25),
            'active_ends_at' => now()->subDay(),
        ], [
            'submissions_end_at' => now()->subDays(15),
            'votes_start_at' => now()->subDays(10),
        ]);

        $category = $this->category($id, self::FILLED_CATEGORY_TITLE, 'La phrase qui donne envie de lire la suite.', 1);

        $winner = $this->entry($category, $this->users[E2eAccountsSeeder::ADMIN_EMAIL], [
            'quote_id' => self::LONE_QUOTE_ID,
            'highlighted_text' => 'à comparer avec la précédente',
        ]);

        $this->entry($category, $this->users[E2eAccountsSeeder::MODERATOR_EMAIL], [
            'quote_id' => 3,
            'highlighted_text' => 'du premier bloc, assez longue',
        ]);

        // `confirmed` voted while it was open: after the end they must still see
        // their own choice, and nothing else.
        $this->vote($category, $winner, $this->users[E2eAccountsSeeder::CONFIRMED_EMAIL]);
        $this->vote($category, $winner, $this->users[E2eAccountsSeeder::MODERATOR_EMAIL]);
    }

    private function activity(string $slug, string $name, array $dates, array $settings): int
    {
        $activity = Activity::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'description' => '<p>Choisissez vos plus beaux passages, puis votez pour ceux des autres.</p>',
            'activity_type' => QuoteContestRegistration::ACTIVITY_TYPE,
            // Decision #1: the whole activity is gated here, so a non-confirmed
            // `user` never reaches the page nor sees it listed.
            'role_restrictions' => [Roles::USER_CONFIRMED, Roles::MODERATOR, Roles::ADMIN],
            'requires_subscription' => false,
            'created_by_user_id' => $this->users[E2eAccountsSeeder::ADMIN_EMAIL] ?? null,
        ], $dates));

        QuoteContestSettings::create(array_merge(['activity_id' => $activity->id], $settings));

        return (int) $activity->id;
    }

    private function category(int $activityId, string $title, string $description, int $position): QuoteContestCategory
    {
        return QuoteContestCategory::create([
            'activity_id' => $activityId,
            'title' => $title,
            'description' => $description,
            'position' => $position,
        ]);
    }

    private function entry(QuoteContestCategory $category, int $userId, array $overrides): QuoteContestEntry
    {
        return QuoteContestEntry::create(array_merge([
            'activity_id' => $category->activity_id,
            'category_id' => $category->id,
            'user_id' => $userId,
            'story_id' => self::STORY_ID,
            'story_title' => self::STORY_TITLE,
            'story_slug' => self::STORY_SLUG,
            'chapter_id' => self::SIMPLE_CHAPTER_ID,
            'chapter_title' => self::SIMPLE_CHAPTER_TITLE,
            'chapter_slug' => self::SIMPLE_CHAPTER_SLUG,
            'author_user_ids' => [$this->users[E2eAccountsSeeder::AUTHOR_EMAIL]],
        ], $overrides));
    }

    private function vote(QuoteContestCategory $category, QuoteContestEntry $entry, int $userId): void
    {
        QuoteContestVote::create([
            'category_id' => $category->id,
            'entry_id' => $entry->id,
            'user_id' => $userId,
        ]);
    }
}
