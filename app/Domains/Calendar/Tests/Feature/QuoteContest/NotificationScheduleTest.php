<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsClosingNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsOpenNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesClosingNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesOpenNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** One run of the cron. */
function tick(TestCase $t): void
{
    $t->artisan('calendar:quote-contest-notify')->assertExitCode(0);
}

/** @return array<int, string> the four broadcast type keys */
function broadcastTypes(): array
{
    return [
        SubmissionsOpenNotification::type(),
        SubmissionsClosingNotification::type(),
        VotesOpenNotification::type(),
        VotesClosingNotification::type(),
    ];
}

function sentCount(string $type): int
{
    return countNotificationsByKey($type);
}

/** @return array<string, int> how many of each broadcast went out */
function countsByType(): array
{
    $counts = [];
    foreach (broadcastTypes() as $type) {
        $counts[$type] = countNotificationsByKey($type);
    }

    return $counts;
}

/**
 * The HTML a recipient actually reads, rebuilt from what was stored — so the
 * round trip through the payload is exercised too.
 *
 * @param class-string<\App\Domains\Notification\Public\Contracts\NotificationContent> $class
 */
function renderedBroadcast(string $class): string
{
    $notification = getLatestNotificationByKey($class::type());
    expect($notification)->not->toBeNull();

    $data = $notification->content_data;
    $data = is_array($data) ? $data : json_decode((string) $data, true);

    return $class::fromData($data)->display();
}

describe('The four broadcasts fire on the tick after their moment', function () {

    it('fires submissions-open once the activity has started', function () {
        $reader = bob($this);

        createQuoteContest($this, [
            'preview_starts_at' => now()->subDay(),
            'active_starts_at' => now()->addHours(2),
            'active_ends_at' => now()->addDays(30),
        ], [
            'submissions_end_at' => now()->addDays(10),
            'votes_start_at' => now()->addDays(12),
        ]);

        tick($this);
        expect(sentCount(SubmissionsOpenNotification::type()))->toBe(0);

        $this->travel(3)->hours();
        tick($this);

        $notification = getLatestNotificationByKey(SubmissionsOpenNotification::type());
        expect($notification)->not->toBeNull()
            ->and(getNotificationTargetUserIds((int) $notification->id))->toContain($reader->id);
    });

    it('fires submissions-closing 24 h before the submissions end', function () {
        $reader = bob($this);

        createQuoteContest($this, [
            'preview_starts_at' => now()->subDays(3),
            'active_starts_at' => now()->subDay(),
            'active_ends_at' => now()->addDays(30),
        ], [
            // 26 h away: still two hours short of the trigger.
            'submissions_end_at' => now()->addHours(26),
            'votes_start_at' => now()->addDays(3),
        ]);

        tick($this);
        expect(sentCount(SubmissionsClosingNotification::type()))->toBe(0);

        $this->travel(3)->hours();
        tick($this);

        $notification = getLatestNotificationByKey(SubmissionsClosingNotification::type());
        expect($notification)->not->toBeNull()
            ->and(getNotificationTargetUserIds((int) $notification->id))->toContain($reader->id);
    });

    it('fires votes-open once the vote period has started', function () {
        $reader = bob($this);

        createQuoteContest($this, [
            'preview_starts_at' => now()->subDays(10),
            'active_starts_at' => now()->subDays(8),
            'active_ends_at' => now()->addDays(30),
        ], [
            'submissions_end_at' => now()->subDay(),
            'votes_start_at' => now()->addHours(2),
        ]);

        tick($this);
        expect(sentCount(VotesOpenNotification::type()))->toBe(0);

        $this->travel(3)->hours();
        tick($this);

        $notification = getLatestNotificationByKey(VotesOpenNotification::type());
        expect($notification)->not->toBeNull()
            ->and(getNotificationTargetUserIds((int) $notification->id))->toContain($reader->id);
    });

    it('fires votes-closing 24 h before the activity ends', function () {
        $reader = bob($this);

        createQuoteContest($this, [
            'preview_starts_at' => now()->subDays(10),
            'active_starts_at' => now()->subDays(8),
            'active_ends_at' => now()->addHours(26),
        ], [
            'submissions_end_at' => now()->subDays(3),
            'votes_start_at' => now()->subDay(),
        ]);

        tick($this);
        expect(sentCount(VotesClosingNotification::type()))->toBe(0);

        $this->travel(3)->hours();
        tick($this);

        $notification = getLatestNotificationByKey(VotesClosingNotification::type());
        expect($notification)->not->toBeNull()
            ->and(getNotificationTargetUserIds((int) $notification->id))->toContain($reader->id);
    });
});

describe('Idempotence is the column', function () {

    it('sends nothing on a second tick, for each of the four', function () {
        bob($this);

        // Every one of the four moments is behind us, and the contest is still
        // running: all four fire on the first tick.
        createQuoteContest($this, [
            'preview_starts_at' => now()->subDays(20),
            'active_starts_at' => now()->subDays(10),
            'active_ends_at' => now()->addHours(2),
        ], [
            'submissions_end_at' => now()->subDays(5),
            'votes_start_at' => now()->subDays(3),
        ]);

        tick($this);
        expect(countsByType())->toBe(array_fill_keys(broadcastTypes(), 1));

        tick($this);
        tick($this);

        expect(countsByType())->toBe(array_fill_keys(broadcastTypes(), 1));
    });

    it('does not re-fire when an admin moves a date forward past a stamped moment', function () {
        bob($this);

        $contest = createQuoteContest($this, [
            'preview_starts_at' => now()->subDays(10),
            'active_starts_at' => now()->subDays(8),
            'active_ends_at' => now()->addDays(30),
        ], [
            'submissions_end_at' => now()->subDays(2),
            'votes_start_at' => now()->subHour(),
        ]);

        tick($this);
        expect(sentCount(VotesOpenNotification::type()))->toBe(1);

        // The admin pushes the vote opening back into the future.
        $contest->settings->update(['votes_start_at' => now()->addHour()]);

        $this->travel(2)->hours();
        tick($this);

        // Re-notifying the whole confirmed user base on a date correction would
        // be spam: the stamp holds.
        expect(sentCount(VotesOpenNotification::type()))->toBe(1);
    });
});

describe('Who is told', function () {

    it('notifies confirmed users only — never a non-confirmed user', function () {
        // The guard on the `createBroadcastNotification()` trap: that API
        // targets `user` as well as `user-confirmed`, and decision #10 does not.
        $confirmed = bob($this);
        $nonConfirmed = daniel($this, ['name' => 'Daniel', 'email' => 'daniel@example.com'], true, [Roles::USER]);
        $moderator = moderator($this);
        $admin = admin($this);

        $this->actingAs($admin);
        createQuoteContest($this, [], [], actorUserId: $admin->id);

        tick($this);

        $notification = getLatestNotificationByKey(SubmissionsOpenNotification::type());
        expect($notification)->not->toBeNull();

        $targets = getNotificationTargetUserIds((int) $notification->id);

        expect($targets)->toContain($confirmed->id)
            ->and($targets)->toContain($moderator->id)
            ->and($targets)->toContain($admin->id)
            ->and($targets)->not->toContain($nonConfirmed->id);
    });

    it('fires on the next tick for a contest whose start is already past when it is created', function () {
        // Late is better than never (§3.4).
        $reader = bob($this);

        createQuoteContest($this, [
            'preview_starts_at' => now()->subDays(3),
            'active_starts_at' => now()->subHours(6),
            'active_ends_at' => now()->addDays(30),
        ], [
            'submissions_end_at' => now()->addDays(10),
            'votes_start_at' => now()->addDays(12),
        ]);

        tick($this);

        $notification = getLatestNotificationByKey(SubmissionsOpenNotification::type());
        expect($notification)->not->toBeNull()
            ->and(getNotificationTargetUserIds((int) $notification->id))->toContain($reader->id);
    });

    it('fires nothing for a draft or an archived activity', function () {
        bob($this);

        // Draft: the activity is not visible to anyone yet, whatever its dates.
        // Its preview date is pushed back afterwards, because the activity form
        // refuses a start earlier than the preview.
        $draft = createQuoteContest($this, [
            'name' => 'Concours brouillon',
            'preview_starts_at' => now()->subDays(2),
            'active_starts_at' => now()->subDay(),
            'active_ends_at' => now()->addHours(2),
        ], [
            'submissions_end_at' => now()->subHours(12),
            'votes_start_at' => now()->subHours(6),
        ]);
        updateActivityVisibilityStartDate($draft->id, now()->addDays(2));

        // Archived: the contest is over and put away.
        createQuoteContest($this, [
            'name' => 'Concours archivé',
            'preview_starts_at' => now()->subDays(20),
            'active_starts_at' => now()->subDays(10),
            'active_ends_at' => now()->subHours(2),
            'archived_at' => now()->subHour(),
        ], [
            'submissions_end_at' => now()->subDays(5),
            'votes_start_at' => now()->subDays(3),
        ]);

        tick($this);

        expect(countsByType())->toBe(array_fill_keys(broadcastTypes(), 0));
    });
});

describe('What the broadcast says', function () {

    it('links to the activity page, and to its Votes tab for the vote broadcasts', function () {
        // The test locale leaves keys untranslated on purpose; the link only
        // exists once the French string is rendered.
        app()->setLocale('fr');
        bob($this);

        $contest = createQuoteContest($this, [
            'name' => 'Concours de citations',
            'preview_starts_at' => now()->subDays(20),
            'active_starts_at' => now()->subDays(10),
            'active_ends_at' => now()->addHours(2),
        ], [
            'submissions_end_at' => now()->subDays(5),
            'votes_start_at' => now()->subDays(3),
        ]);

        tick($this);

        $url = route('calendar.activities.show', $contest->activity->slug);

        expect(renderedBroadcast(SubmissionsOpenNotification::class))
            ->toContain($url)
            ->toContain('Concours de citations')
            ->and(renderedBroadcast(SubmissionsClosingNotification::class))->toContain($url)
            // §4.7: the vote broadcasts land straight on the ballot.
            ->and(renderedBroadcast(VotesOpenNotification::class))->toContain($url . '#votes')
            ->and(renderedBroadcast(VotesClosingNotification::class))->toContain($url . '#votes');
    });

    it('carries the activity name and slug, and nothing else', function () {
        bob($this);

        createQuoteContest($this, ['name' => 'Concours de citations']);

        tick($this);

        $notification = getLatestNotificationByKey(SubmissionsOpenNotification::type());
        $data = $notification->content_data;
        $data = is_array($data) ? $data : json_decode((string) $data, true);

        expect(array_keys($data))->toEqualCanonicalizing(['activity_name', 'activity_slug'])
            ->and($data['activity_name'])->toBe('Concours de citations');
    });

    it('words the four broadcasts in French', function () {
        $keys = ['submissions_open', 'submissions_closing', 'votes_open', 'votes_closing'];

        foreach ($keys as $key) {
            $display = trans('quote-contest::quote-contest.notification.' . $key . '.display', [
                'activity_name' => 'Concours de citations',
                'activity_url' => '/calendrier/concours-de-citations',
            ], 'fr');
            $name = trans('quote-contest::quote-contest.notification.' . $key . '.name', [], 'fr');

            expect($display)
                ->toContain('Concours de citations')
                ->toContain('/calendrier/concours-de-citations')
                ->and($display)->not->toContain('quote-contest::')
                ->and($name)->not->toContain('quote-contest::');
        }
    });
});
