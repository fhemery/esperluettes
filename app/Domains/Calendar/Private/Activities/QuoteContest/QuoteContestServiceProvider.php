<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class QuoteContestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $base = app_path('Domains/Calendar/Private/Activities/QuoteContest');

        $this->loadViewsFrom($base . '/Resources/views', 'quote-contest');
        $this->loadTranslationsFrom($base . '/Resources/lang', 'quote-contest');
        $this->loadMigrationsFrom($base . '/Database/Migrations');
        $this->loadRoutesFrom($base . '/Http/routes.php');

        // The reader page is a class component; the admin config panel is an
        // anonymous one. Both answer to the same `quote-contest::` prefix.
        Blade::componentNamespace('App\\Domains\\Calendar\\Private\\Activities\\QuoteContest\\View\\Components', 'quote-contest');
        Blade::anonymousComponentPath($base . '/Resources/views/components', 'quote-contest');
    }
}
