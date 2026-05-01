<?php

namespace App\Domains\Notification\Public\Providers;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Notification\Private\Console\CleanupOldNotificationsCommand;
use App\Domains\Notification\Private\Listeners\CleanNotificationsOnUserDeleted;
use App\Domains\Notification\Public\Events\NotificationsCleanedUp;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationFactory::class, function () {
            return new NotificationFactory();
        });

        $this->app->singleton(NotificationChannelRegistry::class, function () {
            return new NotificationChannelRegistry();
        });

        $this->commands([
            CleanupOldNotificationsCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(app_path('Domains/Notification/Private/Resources/lang'), 'notifications');
        $this->loadRoutesFrom(app_path('Domains/Notification/Private/routes.php'));

        $this->loadMigrationsFrom(app_path('Domains/Notification/Database/Migrations'));

        $this->loadViewsFrom(app_path('Domains/Notification/Private/Resources/views'), 'notification');
        Blade::componentNamespace('App\\Domains\\Notification\\Private\\View\\Components', 'notification');
        Blade::anonymousComponentPath(app_path('Domains/Notification/Private/Resources/views/components'), 'notification');

        $this->registerGroups();
        $this->registerEvents();
        $this->registerSettingsTab();
    }

    private function registerSettingsTab(): void
    {
        $api = app(SettingsPublicApi::class);

        // Skip if already registered (idempotent for testing — static registry persists across test instances)
        if ($api->getTab('notification') !== null) {
            return;
        }

        $api->registerTab(new SettingsTabDefinition(
            id: 'notification',
            order: 30,
            nameKey: 'notifications::settings.tab_name',
            icon: 'notifications',
            customViewPath: 'notification::settings.settings',
        ));
    }

    private function registerGroups(): void
    {
        $factory = app(NotificationFactory::class);

        $factory->registerGroup('comments',      10, 'story::notification.settings.group_comments');
        $factory->registerGroup('collaboration', 20, 'story::notification.settings.group_collaboration');
        $factory->registerGroup('readlist',      30, 'readlist::notification.settings.group_readlist');
        $factory->registerGroup('news',          40, 'news::notification.settings.group_news');
        $factory->registerGroup('moderation',    50, 'shared::notification.settings.group_moderation');
    }

    private function registerEvents(): void
    {
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(
            NotificationsCleanedUp::name(),
            NotificationsCleanedUp::class
        );

        $eventBus->subscribe(UserDeleted::class, [app(CleanNotificationsOnUserDeleted::class), 'handle']);
    }
}
