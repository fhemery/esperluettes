<?php

declare(strict_types=1);

namespace App\Domains\Editor\Public\Providers;

use App\Domains\Editor\Private\Blocks\ImageBlockType;
use App\Domains\Editor\Private\Blocks\TextBlockType;
use App\Domains\Editor\Public\Blocks\EditorBlockRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class EditorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Built-ins are registered in the factory, not in boot(), so they come
        // first in all() whatever the boot order of plugin providers.
        $this->app->singleton(EditorBlockRegistry::class, function (): EditorBlockRegistry {
            $registry = new EditorBlockRegistry();
            $registry->register(new TextBlockType());
            $registry->register(new ImageBlockType());
            return $registry;
        });
    }

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
