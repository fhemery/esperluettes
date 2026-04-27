<?php

namespace App\Domains\Discord\Public\Providers;

use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Discord\Private\Listeners\CleanDiscordNotificationsOnDisconnect;
use App\Domains\Discord\Private\Listeners\RemoveDiscordAssociationsOnUserDeleted;
use App\Domains\Discord\Private\Services\DiscordNotificationQueueService;
use App\Domains\Discord\Private\Support\DiscordFeatureToggles;
use App\Domains\Discord\Public\Events\DiscordConnected;
use App\Domains\Discord\Public\Events\DiscordDisconnected;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Contracts\NotificationChannelDefinition;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class DiscordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Discord/Database/Migrations'));

        $this->loadTranslationsFrom(app_path('Domains/Discord/Private/Resources/lang'), 'discord');
        $this->loadViewsFrom(app_path('Domains/Discord/Private/Resources/views'), 'discord');

        $this->loadRoutesFrom(app_path('Domains/Discord/Private/api.routes.php'));
        $this->loadRoutesFrom(app_path('Domains/Discord/Private/web.routes.php'));

        // Events
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(DiscordConnected::name(), DiscordConnected::class);
        $eventBus->registerEvent(DiscordDisconnected::name(), DiscordDisconnected::class);
        $eventBus->subscribe(UserDeleted::name(), [RemoveDiscordAssociationsOnUserDeleted::class, 'handle']);
        $eventBus->subscribe(DiscordDisconnected::name(), [CleanDiscordNotificationsOnDisconnect::class, 'handle']);

        // Register Discord as a notification channel
        app(NotificationChannelRegistry::class)->register(new NotificationChannelDefinition(
            id:                  'discord',
            nameTranslationKey:  'discord::notifications.channel_name',
            defaultEnabled:      false,
            sortOrder:           20,
            deliveryCallback:    function (NotificationDto $dto, array $userIds) {
                app(DiscordNotificationQueueService::class)->queue($dto, $userIds);
            },
            featureCheck:        function () {
                return app(ConfigPublicApi::class)->isToggleEnabled(
                    DiscordFeatureToggles::NOTIFICATIONS,
                    DiscordFeatureToggles::DOMAIN
                );
            },
        ));

        Blade::componentNamespace('App\\Domains\\Discord\\Private\\Views\\Components', 'discord');

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('discord.api', \App\Domains\Discord\Private\Middleware\DiscordApiAuth::class);
    }
}
