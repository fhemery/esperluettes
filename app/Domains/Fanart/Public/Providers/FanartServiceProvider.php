<?php

namespace App\Domains\Fanart\Public\Providers;

use App\Domains\Fanart\Private\Support\FanartMediaUsageProvider;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FanartServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Fanart/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/Fanart/Private/web.routes.php'));
        $this->loadRoutesFrom(app_path('Domains/Fanart/Private/api.routes.php'));
        $this->loadTranslationsFrom(app_path('Domains/Fanart/Private/Resources/lang'), 'fanart');
        $this->loadViewsFrom(app_path('Domains/Fanart/Private/Resources/views'), 'fanart');
        Blade::anonymousComponentPath(app_path('Domains/Fanart/Private/Resources/views/components'), 'fanart');
        Blade::componentNamespace('App\\Domains\\Fanart\\Private\\View\\Components', 'fanart');

        // Media skips the whole private `fanart/` root unless a provider claims files under it.
        app(MediaUsageRegistry::class)->register(new FanartMediaUsageProvider());
    }
}
