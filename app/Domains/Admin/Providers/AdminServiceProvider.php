<?php  
namespace App\Domains\Admin\Providers;

use App\Domains\Admin\Http\Middleware\InjectFilamentUserName;
use Illuminate\Auth\Middleware\Authenticate;
use App\Domains\Auth\Public\Middleware\CheckRole;
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
use Illuminate\Support\Facades\Route;
use App\Domains\Admin\Controllers\LogDownloadController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\DB;

// Specific extension to use Filament
class AdminServiceProvider extends PanelProvider
{
    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'admin');

        // Views for Filament pages within this domain
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'admin');

        // Override Filament's logout endpoint to redirect to app logout
        Route::middleware(['web'])
            ->prefix('admin')
            ->group(function () {
                Route::post('/logout', [\App\Domains\Admin\Controllers\FilamentLogoutController::class, '__invoke'])
                    ->name('filament.admin.auth.logout');
            });

        // Admin tech routes (logs download)
        Route::middleware(['web', Authenticate::class, CheckRole::class . ':admin,tech-admin'])
            ->prefix('admin')
            ->group(function () {
                Route::get('/logs/download', LogDownloadController::class)->name('admin.logs.download');
            });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl('/')
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
                InjectFilamentUserName::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                CheckRole::class . ':admin,tech-admin',
            ]);
    }
}

