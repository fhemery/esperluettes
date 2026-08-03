<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Console;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsClosingNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsOpenNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesClosingNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesOpenNotification;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Contracts\NotificationContent;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The contest's four date-triggered broadcasts, on a 5-minute cron (§3.4).
 *
 * Idempotence is a column, not a lock: each broadcast has its own
 * `notified_*_at` on the settings row, stamped in the same transaction as the
 * send, so a double tick, a redeploy mid-run or a replayed cron sends nothing
 * twice. Two consequences are deliberate — a contest whose start is already
 * past when it is created fires "submissions open" on the next tick, and moving
 * a date forward past a stamped moment re-fires nothing.
 *
 * The scan is a full table read: `calendar_quote_contest_settings` holds one row
 * per contest ever created, which is a handful.
 */
class NotifyQuoteContestCommand extends Command
{
    protected $signature = 'calendar:quote-contest-notify';

    protected $description = 'Send the quote contest broadcasts whose trigger moment has passed';

    public function handle(AuthPublicApi $auth, NotificationPublicApi $notifications): int
    {
        $now = CarbonImmutable::now();

        /** @var array<int, int>|null $recipients resolved on the first send only */
        $recipients = null;
        $sent = 0;

        foreach (QuoteContestSettings::query()->with('activity')->get() as $settings) {
            $activity = $settings->activity;

            if ($activity === null || $this->isDraftOrArchived($activity)) {
                continue;
            }

            foreach ($this->dueBroadcasts($settings, $activity, $now) as $column => $content) {
                // Decision #10: confirmed users only. `createBroadcastNotification()`
                // would also reach the non-confirmed `user` role, for whom the
                // activity — and so the link — does not exist.
                $recipients ??= $auth->getUserIdsByRoles([Roles::USER_CONFIRMED], activeOnly: true);

                if ($recipients === []) {
                    $this->warn('No confirmed user to notify; nothing sent and nothing stamped.');

                    return self::SUCCESS;
                }

                DB::transaction(function () use ($notifications, $recipients, $content, $settings, $column, $now): void {
                    $notifications->createNotification($recipients, $content);
                    $settings->forceFill([$column => $now])->save();
                });

                $sent++;
            }
        }

        $this->info("Sent: {$sent} quote contest broadcast(s).");

        return self::SUCCESS;
    }

    /**
     * A draft activity is visible to nobody yet and an archived one is put
     * away: neither has an audience worth telling anything.
     */
    private function isDraftOrArchived(Activity $activity): bool
    {
        return in_array($activity->state, [ActivityState::DRAFT, ActivityState::ARCHIVED], true);
    }

    /**
     * The broadcasts whose moment has passed and whose column is still null,
     * keyed by the column that records them.
     *
     * @return array<string, NotificationContent>
     */
    private function dueBroadcasts(QuoteContestSettings $settings, Activity $activity, CarbonImmutable $now): array
    {
        $name = (string) $activity->name;
        $slug = (string) $activity->slug;
        $due = [];

        // Submissions open with the activity itself, and close on the contest's
        // own date; the votes open on theirs and close with the activity.
        if ($settings->notified_submissions_open_at === null
            && $this->hasPassed($activity->active_starts_at, $now)) {
            $due['notified_submissions_open_at'] = new SubmissionsOpenNotification($name, $slug);
        }

        if ($settings->notified_submissions_closing_at === null
            && $this->hasPassed($settings->submissions_end_at, $now, leadHours: 24)) {
            $due['notified_submissions_closing_at'] = new SubmissionsClosingNotification($name, $slug);
        }

        if ($settings->notified_votes_open_at === null
            && $this->hasPassed($settings->votes_start_at, $now)) {
            $due['notified_votes_open_at'] = new VotesOpenNotification($name, $slug);
        }

        if ($settings->notified_votes_closing_at === null
            && $this->hasPassed($activity->active_ends_at, $now, leadHours: 24)) {
            $due['notified_votes_closing_at'] = new VotesClosingNotification($name, $slug);
        }

        return $due;
    }

    /**
     * Has `$moment` minus its warning lead gone by? A null moment never
     * triggers — the activity dates are nullable, and a contest with no start
     * has not started.
     */
    private function hasPassed(?DateTimeInterface $moment, CarbonImmutable $now, int $leadHours = 0): bool
    {
        if ($moment === null) {
            return false;
        }

        return CarbonImmutable::instance($moment)->subHours($leadHours)->lessThanOrEqualTo($now);
    }
}
