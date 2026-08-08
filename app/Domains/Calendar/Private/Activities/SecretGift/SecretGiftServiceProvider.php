<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Calendar\Private\Activities\SecretGift\Console\ShuffleSecretGiftCommand;
use App\Domains\Calendar\Private\Activities\SecretGift\Listeners\RemoveParticipantOnUserRemoved;
use App\Domains\Calendar\Private\Activities\SecretGift\Support\SecretGiftMediaUsageProvider;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class SecretGiftServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            ShuffleSecretGiftCommand::class,
        ]);
    }

    public function boot(): void
    {
        $base = app_path('Domains/Calendar/Private/Activities/SecretGift');

        $this->loadViewsFrom($base . '/Resources/views', 'secret-gift');
        $this->loadTranslationsFrom($base . '/Resources/lang', 'secret-gift');
        $this->loadMigrationsFrom($base . '/Database/Migrations');
        $this->loadRoutesFrom($base . '/Http/routes.php');

        // The reader page is a class component; the admin config panel is an
        // anonymous one. Both answer to the same `secret-gift::` prefix.
        Blade::componentNamespace('App\\Domains\\Calendar\\Private\\Activities\\SecretGift\\View\\Components', 'secret-gift');
        Blade::anonymousComponentPath($base . '/Resources/views/components', 'secret-gift');

        // Let Media GC know which private image files gifts still use.
        app(MediaUsageRegistry::class)->register(new SecretGiftMediaUsageProvider());

        $this->registerEventListeners();
    }

    /**
     * A user leaving the site before the shuffle is un-enrolled automatically
     * (functional spec §5). Both Auth events funnel into the same listener.
     *
     * The listener is resolved when an event fires, not here: building it at
     * boot would drag the Media public API its service depends on into the
     * container on every single request, and freeze whatever it holds at boot
     * time — see `QuoteContestServiceProvider::registerEventListeners()`.
     */
    private function registerEventListeners(): void
    {
        /** @var EventBus $eventBus */
        $eventBus = app(EventBus::class);

        $eventBus->subscribe(
            UserDeleted::class,
            static fn (UserDeleted $event) => app(RemoveParticipantOnUserRemoved::class)->handle($event),
        );

        $eventBus->subscribe(
            UserDeactivated::class,
            static fn (UserDeactivated $event) => app(RemoveParticipantOnUserRemoved::class)->handle($event),
        );
    }
}
