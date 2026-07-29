<?php

declare(strict_types=1);

namespace App\Domains\Editor\Public\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class EditorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Views + anonymous components under the 'editor' namespace
        // (<x-editor::rich-text>, <x-editor::multi>). Prefixed only: there is
        // deliberately no unprefixed alias.
        $this->loadViewsFrom(app_path('Domains/Editor/Private/Resources/views'), 'editor');
        Blade::anonymousComponentPath(app_path('Domains/Editor/Private/Resources/views/components'), 'editor');

        // Translations (editor::rich-text.*, editor::multi.*)
        $this->loadTranslationsFrom(app_path('Domains/Editor/Private/Resources/lang'), 'editor');
    }
}
