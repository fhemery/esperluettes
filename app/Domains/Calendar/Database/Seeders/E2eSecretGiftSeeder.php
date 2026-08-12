<?php

namespace App\Domains\Calendar\Database\Seeders;

use App\Domains\Auth\Database\Seeders\E2eAccountsSeeder;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftSettings;
use App\Domains\Calendar\Private\Activities\SecretGift\SecretGiftRegistration;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Eight *Cadeau surprise* activities for the E2E environment (see .env.e2e),
 * mirrored in `e2e/support/fixtures.ts`.
 *
 * An activity's state and its registration window both come from the clock
 * alone, and the shuffle is a one-way door, so every combination the enrolment
 * screens react to needs an activity of its own: there is no way to fast-forward
 * one, and no way to un-shuffle another.
 *
 * `confirmed` is the reader every spec drives, so each activity is defined by
 * where that account stands in it — enrolled, not enrolled, or locked out.
 *
 * Assignments are written directly rather than through `ShuffleService`, whose
 * pairing is random by design; a spec that has to name the recipient on screen
 * needs to know who it is.
 */
class E2eSecretGiftSeeder extends Seeder
{
    /** Registration open, `confirmed` not enrolled — the join form and its editor. */
    public const OPEN_SLUG = 'cadeau-surprise-ouvert';
    /** Same, kept apart so the join → edit → leave journey never disturbs the read-only checks. */
    public const JOINABLE_SLUG = 'cadeau-surprise-rejoindre';
    /** Registration open, `confirmed` enrolled — edit form, leave action, participant list. */
    public const ENROLLED_SLUG = 'cadeau-surprise-inscrit';
    /** Registration open, `confirmed` the only participant — the *alone* line, and a shuffle short of 2. */
    public const ALONE_SLUG = 'cadeau-surprise-seul';
    /** Still PREVIEW, deadline already passed — the read-only closed state. */
    public const CLOSED_SLUG = 'cadeau-surprise-closes';
    /** Still PREVIEW and the deadline still ahead, but shuffled — registration closed early. */
    public const SHUFFLED_SLUG = 'cadeau-surprise-tire';
    /** ACTIVE and shuffled — the pre-existing two-tab gift UI. */
    public const ACTIVE_SLUG = 'cadeau-surprise-actif';
    /** Registration open, three participants, never shuffled — the one a spec may shuffle. */
    public const SHUFFLE_ME_SLUG = 'cadeau-surprise-a-tirer';
    /** Restricted to admins: every other role must get a 404, not a page with the button missing. */
    public const RESTRICTED_SLUG = 'cadeau-surprise-reserve';

    /**
     * `author`'s preferences on the open activity. Private to whoever draws them,
     * so this string must appear on no participant list and on no admin panel.
     */
    public const SECRET_PREFERENCES = 'Je collectionne les hérissons en chocolat';

    /**
     * `author`'s preferences on the active activity, where `confirmed` is their
     * assigned giver — the one place a spec must find them.
     */
    public const RECIPIENT_PREFERENCES = 'Je collectionne les timbres de dragons';

    /** @var array<string, int> email => user id */
    private array $users = [];

    public function run(): void
    {
        if (Activity::where('slug', self::OPEN_SLUG)->exists()) {
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

        $confirmed = $this->users[E2eAccountsSeeder::CONFIRMED_EMAIL];
        $author = $this->users[E2eAccountsSeeder::AUTHOR_EMAIL];
        $admin = $this->users[E2eAccountsSeeder::ADMIN_EMAIL];
        $moderator = $this->users[E2eAccountsSeeder::MODERATOR_EMAIL];

        $previewOpen = [
            'preview_starts_at' => now()->subDays(2),
            'active_starts_at' => now()->addDays(10),
            'active_ends_at' => now()->addDays(30),
        ];

        // `confirmed` is a stranger here: join form, no participant list.
        $open = $this->activity(self::OPEN_SLUG, 'Cadeau surprise — inscriptions ouvertes', $previewOpen, now()->addDays(5));
        $this->join($open, $author, '<p>'.self::SECRET_PREFERENCES.'.</p>');
        $this->join($open, $admin);
        $this->join($open, $moderator);

        // The one a spec joins and leaves again.
        $joinable = $this->activity(self::JOINABLE_SLUG, 'Cadeau surprise — à rejoindre', $previewOpen, now()->addDays(5));
        $this->join($joinable, $author);
        $this->join($joinable, $admin);

        // `confirmed` already in, with three others to list.
        $enrolled = $this->activity(self::ENROLLED_SLUG, 'Cadeau surprise — déjà inscrit', $previewOpen, now()->addDays(6));
        $this->join($enrolled, $confirmed, '<p>Ce que j\'aime : les chats.</p>');
        $this->join($enrolled, $author);
        $this->join($enrolled, $admin);
        $this->join($enrolled, $moderator);

        // Nobody else yet — the *alone* line, and a shuffle that cannot run.
        $alone = $this->activity(self::ALONE_SLUG, 'Cadeau surprise — seul(e) inscrit(e)', $previewOpen, now()->addDays(5));
        $this->join($alone, $confirmed);

        // The deadline shut the window while the activity is still in preview.
        $closed = $this->activity(self::CLOSED_SLUG, 'Cadeau surprise — inscriptions closes', [
            'preview_starts_at' => now()->subDays(5),
            'active_starts_at' => now()->addDays(10),
            'active_ends_at' => now()->addDays(30),
        ], now()->subDay());
        $this->join($closed, $confirmed, '<p>Ce que j\'aime : les livres anciens.</p>');
        $this->join($closed, $admin);

        // The shuffle shut it instead, days before the declared deadline.
        $shuffled = $this->activity(self::SHUFFLED_SLUG, 'Cadeau surprise — tirage déjà fait', $previewOpen, now()->addDays(7));
        $this->join($shuffled, $confirmed);
        $this->join($shuffled, $author);
        $this->join($shuffled, $admin);
        $this->assign($shuffled, [$confirmed, $author, $admin]);

        // Running: the gift UI this feature must have left alone.
        $active = $this->activity(self::ACTIVE_SLUG, 'Cadeau surprise — en cours', [
            'preview_starts_at' => now()->subDays(10),
            'active_starts_at' => now()->subDays(2),
            'active_ends_at' => now()->addDays(10),
        ], now()->subDays(8));
        $this->join($active, $confirmed);
        $this->join($active, $author, '<p>'.self::RECIPIENT_PREFERENCES.'.</p>');
        $this->join($active, $admin);
        // Circular, so `confirmed` gives to `author` and a spec can name them.
        $this->assign($active, [$confirmed, $author, $admin]);

        // Three participants and no assignment: the shuffle button is live.
        $toShuffle = $this->activity(self::SHUFFLE_ME_SLUG, 'Cadeau surprise — tirage à lancer', $previewOpen, now()->addDays(5));
        $this->join($toShuffle, $confirmed);
        $this->join($toShuffle, $author);
        $this->join($toShuffle, $admin);

        $this->activity(self::RESTRICTED_SLUG, 'Cadeau surprise — réservé', $previewOpen, now()->addDays(5), [Roles::ADMIN]);
    }

    private function activity(string $slug, string $name, array $dates, mixed $registrationEndsAt, ?array $roles = null): Activity
    {
        $activity = Activity::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'description' => '<p>Offrez un cadeau à une personne tirée au sort, et recevez-en un.</p>',
            'activity_type' => SecretGiftRegistration::ACTIVITY_TYPE,
            'role_restrictions' => $roles ?? [Roles::USER_CONFIRMED, Roles::MODERATOR, Roles::ADMIN],
            'created_by_user_id' => $this->users[E2eAccountsSeeder::ADMIN_EMAIL] ?? null,
        ], $dates));

        SecretGiftSettings::create([
            'activity_id' => $activity->id,
            'registration_ends_at' => $registrationEndsAt,
        ]);

        return $activity;
    }

    private function join(Activity $activity, int $userId, ?string $preferences = null): void
    {
        SecretGiftParticipant::create([
            'activity_id' => $activity->id,
            'user_id' => $userId,
            'preferences' => $preferences,
        ]);
    }

    /** Circular assignment over the given order: each gives to the next, the last to the first. */
    private function assign(Activity $activity, array $userIds): void
    {
        foreach ($userIds as $index => $giver) {
            SecretGiftAssignment::create([
                'activity_id' => $activity->id,
                'giver_user_id' => $giver,
                'recipient_user_id' => $userIds[($index + 1) % count($userIds)],
            ]);
        }
    }
}
