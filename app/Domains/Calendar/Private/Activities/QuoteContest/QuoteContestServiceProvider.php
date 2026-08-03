<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest;

use App\Domains\Calendar\Private\Activities\QuoteContest\Console\NotifyQuoteContestCommand;
use App\Domains\Calendar\Private\Activities\QuoteContest\Listeners\WithdrawEntriesOnStoryIneligible;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\EntryRemovedNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsClosingNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsOpenNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesClosingNotification;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesOpenNotification;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Story\Public\Events\StoryExcludedFromEvents;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class QuoteContestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $base = app_path('Domains/Calendar/Private/Activities/QuoteContest');

        $this->loadViewsFrom($base . '/Resources/views', 'quote-contest');
        $this->loadTranslationsFrom($base . '/Resources/lang', 'quote-contest');
        $this->loadMigrationsFrom($base . '/Database/Migrations');
        $this->loadRoutesFrom($base . '/Http/routes.php');

        // The reader page is a class component; the admin config panel is an
        // anonymous one. Both answer to the same `quote-contest::` prefix.
        Blade::componentNamespace('App\\Domains\\Calendar\\Private\\Activities\\QuoteContest\\View\\Components', 'quote-contest');
        Blade::anonymousComponentPath($base . '/Resources/views/components', 'quote-contest');

        // Scheduled from `bootstrap/app.php`, every five minutes.
        $this->commands([
            NotifyQuoteContestCommand::class,
        ]);

        $this->registerEventListeners();
        $this->registerNotifications();
    }

    /**
     * The contest owns its notifications (decision #9), under a Calendar-wide
     * group so a second activity that notifies reuses it rather than adding
     * one. Sort order 70 is the first free slot after Quote's 60.
     *
     * `NotificationFactory` is a bare registry with no constructor: resolving
     * it here pulls no cross-domain API into the container, unlike the listener
     * of `registerEventListeners()`.
     */
    private function registerNotifications(): void
    {
        /** @var NotificationFactory $factory */
        $factory = app(NotificationFactory::class);

        $factory->registerGroup(
            id: 'calendar',
            sortOrder: 70,
            translationKey: 'calendar::notification.groups.calendar',
        );

        $factory->register(
            type: EntryRemovedNotification::type(),
            class: EntryRemovedNotification::class,
            groupId: 'calendar',
            nameKey: 'quote-contest::quote-contest.notification.entry_removed.name',
        );

        // The four date-triggered broadcasts, sent by
        // `calendar:quote-contest-notify` to confirmed users (decision #10).
        $factory->register(
            type: SubmissionsOpenNotification::type(),
            class: SubmissionsOpenNotification::class,
            groupId: 'calendar',
            nameKey: 'quote-contest::quote-contest.notification.submissions_open.name',
        );

        $factory->register(
            type: SubmissionsClosingNotification::type(),
            class: SubmissionsClosingNotification::class,
            groupId: 'calendar',
            nameKey: 'quote-contest::quote-contest.notification.submissions_closing.name',
        );

        $factory->register(
            type: VotesOpenNotification::type(),
            class: VotesOpenNotification::class,
            groupId: 'calendar',
            nameKey: 'quote-contest::quote-contest.notification.votes_open.name',
        );

        $factory->register(
            type: VotesClosingNotification::type(),
            class: VotesClosingNotification::class,
            groupId: 'calendar',
            nameKey: 'quote-contest::quote-contest.notification.votes_closing.name',
        );
    }

    /**
     * A quoted story losing its visibility withdraws the entries drawn from it
     * (§2.3). Both events funnel into the same listener.
     *
     * The listener is resolved when an event fires, not here: building it at
     * boot would drag the Quote and Story public APIs into the container on
     * every single request, and freeze whatever they hold at boot time.
     */
    private function registerEventListeners(): void
    {
        /** @var EventBus $eventBus */
        $eventBus = app(EventBus::class);

        $eventBus->subscribe(
            StoryVisibilityChanged::class,
            static fn (StoryVisibilityChanged $event) => app(WithdrawEntriesOnStoryIneligible::class)
                ->handleVisibilityChanged($event),
        );

        $eventBus->subscribe(
            StoryExcludedFromEvents::class,
            static fn (StoryExcludedFromEvents $event) => app(WithdrawEntriesOnStoryIneligible::class)
                ->handleExcludedFromEvents($event),
        );
    }
}
