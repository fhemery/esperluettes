<?php

namespace App\Domains\Quote\Public\Providers;

use Illuminate\Support\ServiceProvider;

class QuoteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Quote/Database/Migrations'));
    }
}
