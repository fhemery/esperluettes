<?php

namespace App\Domains\Admin\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use App\Domains\Auth\Middleware\CheckRole;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Specific extension to use Filament
class AdminServiceProvider extends PanelProvider
{
    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Load Admin domain translations
        // JSON translations (global, non-namespaced)
        $this->loadJsonTranslationsFrom(__DIR__ . '/../Resources/lang');
        // PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'admin');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                // Resources will be auto-discovered
            ])
            ->discoverResources(in: app_path('Domains/Admin/Filament/Resources'), for: 'App\\Domains\\Admin\\Filament\\Resources')
            ->discoverPages(in: app_path('Domains/Admin/Filament/Pages'), for: 'App\\Domains\\Admin\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Domains/Admin/Filament/Widgets'), for: 'App\\Domains\\Admin\\Filament\\Widgets')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \Illuminate\Session\Middleware\AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                CheckRole::class . ':admin',
            ]);
    }
}
