<?php

namespace App\Domains\Notification\Public\Providers;

use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(app_path('Domains/Notification/Private/Resources/lang'), 'notifications');
        //$this->loadRoutesFrom(app_path('Domains/Notification/Private/routes.php'));

        $this->loadMigrationsFrom(app_path('Domains/Notification/Database/Migrations'));
    }
}
