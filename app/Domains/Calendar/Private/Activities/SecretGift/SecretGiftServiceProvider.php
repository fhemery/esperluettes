<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift;

use App\Domains\Calendar\Private\Activities\SecretGift\Console\ShuffleSecretGiftCommand;
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
        $this->loadViewsFrom(app_path('Domains/Calendar/Private/Activities/SecretGift/Resources/views'), 'secret-gift');
        $this->loadTranslationsFrom(app_path('Domains/Calendar/Private/Activities/SecretGift/Resources/lang'), 'secret-gift');
        $this->loadMigrationsFrom(app_path('Domains/Calendar/Private/Activities/SecretGift/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/Calendar/Private/Activities/SecretGift/Http/routes.php'));

        Blade::componentNamespace('App\\Domains\\Calendar\\Private\\Activities\\SecretGift\\View\\Components', 'secret-gift');
    }
}
