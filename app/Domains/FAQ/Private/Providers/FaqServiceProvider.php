<?php

namespace App\Domains\FAQ\Private\Providers;

use Illuminate\Support\ServiceProvider;

class FaqServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/FAQ/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/FAQ/Private/routes.php'));
        $this->loadViewsFrom(app_path('Domains/FAQ/Private/Resources/views'), 'faq');
        $this->loadTranslationsFrom(app_path('Domains/FAQ/Private/Resources/lang'), 'faq');
    }
}
