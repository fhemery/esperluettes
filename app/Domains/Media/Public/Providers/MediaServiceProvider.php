<?php

declare(strict_types=1);

namespace App\Domains\Media\Public\Providers;

use App\Domains\Media\Private\Console\MediaGcCommand;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrations from consumer providers must persist for the app lifetime.
        $this->app->singleton(MediaUsageRegistry::class);
    }

    public function boot(): void
    {
        // Routes (media library picker endpoint)
        $this->loadRoutesFrom(app_path('Domains/Media/Private/routes.php'));

        // Views + anonymous components under the 'media' namespace (<x-media::image>)
        $this->loadViewsFrom(app_path('Domains/Media/Private/Resources/views'), 'media');
        Blade::anonymousComponentPath(app_path('Domains/Media/Private/Resources/views/components'), 'media');

        // Translations
        $this->loadTranslationsFrom(app_path('Domains/Media/Public/Resources/lang'), 'media');

        // Console
        if ($this->app->runningInConsole()) {
            $this->commands([
                MediaGcCommand::class,
            ]);
        }
    }
}
