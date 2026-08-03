<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest;

use App\Domains\Calendar\Private\Activities\QuoteContest\Listeners\WithdrawEntriesOnStoryIneligible;
use App\Domains\Events\Public\Api\EventBus;
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

        $this->registerEventListeners();
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
