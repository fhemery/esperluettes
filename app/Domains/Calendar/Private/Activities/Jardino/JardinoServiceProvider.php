<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class JardinoServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Views for Jardino-specific components (namespace: 'jardino')
        $this->loadViewsFrom(app_path('Domains/Calendar/Private/Activities/Jardino/Resources/views'), 'jardino');

        // Translations (keep using the 'calendar' namespace for unified lookups)
        $this->loadTranslationsFrom(app_path('Domains/Calendar/Private/Activities/Jardino/Resources/lang'), 'jardino');

        // Register a component namespace so 'jardino::jardino-component' resolves to the class
        Blade::componentNamespace('App\\Domains\\Calendar\\Private\\Activities\\Jardino\\View\\Components', 'jardino');
    }
}
