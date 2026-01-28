<?php

namespace App\Domains\Statistics\Public\Providers;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Statistics\Private\Console\ComputeStatisticCommand;
use App\Domains\Statistics\Private\Definitions\Global\TotalUsersStatistic;
use App\Domains\Statistics\Private\Listeners\UpdateStatisticsOnEvent;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Private\Services\StatisticQueryService;
use App\Domains\Statistics\Private\Services\StatisticRegistry;
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
    }

    private function registerEventListeners(): void
    {
        $eventBus = app(EventBus::class);
        $listener = app(UpdateStatisticsOnEvent::class);

        $eventBus->subscribe(UserRegistered::class, [$listener, 'handle']);
        $eventBus->subscribe(UserDeleted::class, [$listener, 'handle']);
    }
}
