<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift;

use App\Domains\Calendar\Private\Activities\SecretGift\Console\ShuffleSecretGiftCommand;
use App\Domains\Calendar\Private\Activities\SecretGift\Support\SecretGiftMediaUsageProvider;
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
    }
}
