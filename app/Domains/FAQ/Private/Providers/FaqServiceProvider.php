<?php

namespace App\Domains\FAQ\Private\Providers;

use Illuminate\Support\ServiceProvider;

class FaqServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/FAQ/Database/Migrations'));
    }
}
