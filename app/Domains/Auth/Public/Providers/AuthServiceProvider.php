<?php

namespace App\Domains\Auth\Public\Providers;

use App\Domains\Auth\Public\Events\EmailVerified;
use App\Domains\Auth\Public\Events\PasswordChanged;
use App\Domains\Auth\Public\Events\PasswordResetRequested;
use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserLoggedIn;
use App\Domains\Auth\Public\Events\UserLoggedOut;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Auth\Public\Events\UserRoleGranted;
use App\Domains\Auth\Public\Events\UserRoleRevoked;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Config\Public\Contracts\ConfigParameterType;
use App\Domains\Config\Public\Contracts\ConfigParameterVisibility;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(app_path('Domains/Auth/Database/Migrations'));

        // Register JSON translations (kept as-is for now)
        $this->loadJsonTranslationsFrom(app_path('Domains/Auth/Private/Resources/lang'));

        // Keep PHP translations namespace in case files exist later
        $this->loadTranslationsFrom(app_path('Domains/Auth/Private/Resources/lang'), 'auth');

        // Register auth views namespace
        $this->loadViewsFrom(app_path('Domains/Auth/Private/Resources/views'), 'auth');

        // Register domain routes within the web middleware group
        Route::middleware(['web'])->group(app_path('Domains/Auth/Private/routes.php'));

        // Bind middleware aliases used externally (e.g., Admin)
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('role', \App\Domains\Auth\Public\Middleware\CheckRole::class);

        // Register Auth domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(UserRegistered::name(), UserRegistered::class);
        $eventBus->registerEvent(EmailVerified::name(), EmailVerified::class);
        $eventBus->registerEvent(PasswordChanged::name(), PasswordChanged::class);
        $eventBus->registerEvent(PasswordResetRequested::name(), PasswordResetRequested::class);
        $eventBus->registerEvent(UserLoggedIn::name(), UserLoggedIn::class);
        $eventBus->registerEvent(UserLoggedOut::name(), UserLoggedOut::class);
        $eventBus->registerEvent(UserRoleGranted::name(), UserRoleGranted::class);
        $eventBus->registerEvent(UserRoleRevoked::name(), UserRoleRevoked::class);
        $eventBus->registerEvent(UserDeactivated::name(), UserDeactivated::class);
        $eventBus->registerEvent(UserReactivated::name(), UserReactivated::class);
        $eventBus->registerEvent(UserDeleted::name(), UserDeleted::class);

        // Register configuration parameters
        $this->registerConfigParameters();
    }

    protected function registerConfigParameters(): void
    {
        $configApi = app(ConfigPublicApi::class);

        $configApi->registerParameter(new ConfigParameterDefinition(
            domain: AuthConfigKeys::DOMAIN,
            key: AuthConfigKeys::REQUIRE_ACTIVATION_CODE,
            type: ConfigParameterType::BOOL,
            default: true,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $configApi->registerParameter(new ConfigParameterDefinition(
            domain: AuthConfigKeys::DOMAIN,
            key: AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD,
            type: ConfigParameterType::INT,
            default: 5,
            constraints: ['min' => 0],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $configApi->registerParameter(new ConfigParameterDefinition(
            domain: AuthConfigKeys::DOMAIN,
            key: AuthConfigKeys::NON_CONFIRMED_TIMESPAN,
            type: ConfigParameterType::TIME,
            default: 604800, // 7 days in seconds
            constraints: ['min' => 0],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));
    }
}
