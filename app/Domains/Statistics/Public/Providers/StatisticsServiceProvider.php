<?php

namespace App\Domains\Statistics\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Statistics\Private\Console\ComputeStatisticCommand;
use App\Domains\Statistics\Private\Definitions\Global\TotalChaptersStatistic;
use App\Domains\Statistics\Private\Definitions\Global\TotalStoriesStatistic;
use App\Domains\Statistics\Private\Definitions\Global\TotalUsersStatistic;
use App\Domains\Statistics\Private\Definitions\Global\TotalWordsStatistic;
use App\Domains\Statistics\Private\Definitions\User\UserTotalChaptersStatistic;
use App\Domains\Statistics\Private\Definitions\User\UserTotalStoriesStatistic;
use App\Domains\Statistics\Private\Definitions\User\UserTotalWordsStatistic;
use App\Domains\Statistics\Private\Listeners\UpdateStatisticsOnEvent;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Private\Services\StatisticQueryService;
use App\Domains\Statistics\Private\Services\StatisticRegistry;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\StoryDeleted;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StatisticRegistry::class);
        $this->app->singleton(StatisticComputeService::class);
        $this->app->singleton(StatisticQueryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Statistics/Database/Migrations'));
        $this->loadTranslationsFrom(app_path('Domains/Statistics/Private/Resources/lang'), 'statistics');
        $this->loadRoutesFrom(app_path('Domains/Statistics/Private/routes.php'));
        $this->loadViewsFrom(app_path('Domains/Statistics/Private/Resources/views'), 'statistics');

        Blade::anonymousComponentPath(
            app_path('Domains/Statistics/Private/Resources/views/components'),
            'statistics'
        );

        $this->registerStatistics();
        $this->registerEventListeners();
        $this->registerCommands();
        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerGroup('statistics', 'statistics::admin.nav_group', 55);

        $registry->registerPage(
            'statistics.admin',
            'statistics',
            'statistics::admin.nav_label',
            AdminRegistryTarget::route('statistics.admin.index'),
            'bar_chart',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ComputeStatisticCommand::class,
            ]);
        }
    }

    private function registerStatistics(): void
    {
        $registry = app(StatisticRegistry::class);

        $registry->register(TotalUsersStatistic::class);
        $registry->register(TotalStoriesStatistic::class);
        $registry->register(TotalChaptersStatistic::class);
        $registry->register(TotalWordsStatistic::class);
        $registry->register(UserTotalStoriesStatistic::class);
        $registry->register(UserTotalChaptersStatistic::class);
        $registry->register(UserTotalWordsStatistic::class);
    }

    private function registerEventListeners(): void
    {
        $eventBus = app(EventBus::class);

        foreach ([
            UserRegistered::class,
            UserDeleted::class,
            StoryCreated::class,
            StoryDeleted::class,
            ChapterCreated::class,
            ChapterDeleted::class,
            ChapterUpdated::class,
        ] as $eventClass) {
            $eventBus->subscribe($eventClass, [UpdateStatisticsOnEvent::class, 'handle']);
        }
    }
}
