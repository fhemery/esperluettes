<?php

namespace App\Domains\Auth\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\EmailVerified;
use App\Domains\Auth\Public\Events\PasswordChanged;
use App\Domains\Auth\Public\Events\PasswordResetRequested;
use App\Domains\Auth\Public\Events\PromotionAccepted;
use App\Domains\Auth\Public\Events\PromotionRejected;
use App\Domains\Auth\Public\Events\PromotionRequested;
use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserLoggedIn;
use App\Domains\Auth\Public\Events\UserLoggedOut;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Auth\Public\Events\UserRoleGranted;
use App\Domains\Auth\Public\Events\UserRoleRevoked;
use App\Domains\Auth\Public\Notifications\PromotionAcceptedNotification;
use App\Domains\Auth\Public\Notifications\PromotionRejectedNotification;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use App\Domains\Config\Public\Contracts\ConfigParameterVisibility;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Services\NotificationFactory;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
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

        // Keep PHP translations namespace in case files exist later
        $this->loadTranslationsFrom(app_path('Domains/Auth/Private/Resources/lang'), 'auth');

        // Register auth views namespace
        $this->loadViewsFrom(app_path('Domains/Auth/Private/Resources/views'), 'auth');

        // Register PHP components
        Blade::componentNamespace('App\\Domains\\Auth\\Private\\View\\Components', 'auth');

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
        $eventBus->registerEvent(PromotionRequested::name(), PromotionRequested::class);
        $eventBus->registerEvent(PromotionAccepted::name(), PromotionAccepted::class);
        $eventBus->registerEvent(PromotionRejected::name(), PromotionRejected::class);

        // Register configuration parameters
        $this->registerConfigParameters();

        // Register notification content types
        $notificationFactory = app(NotificationFactory::class);
        $notificationFactory->register(
            type: PromotionAcceptedNotification::type(),
            class: PromotionAcceptedNotification::class
        );
        $notificationFactory->register(
            type: PromotionRejectedNotification::type(),
            class: PromotionRejectedNotification::class
        );

        $this->registerAdminNavigation();
    }

    protected function registerConfigParameters(): void
    {
        $configApi = app(ConfigPublicApi::class);

        $configApi->registerParameter(new ConfigParameterDefinition(
            domain: AuthConfigKeys::DOMAIN,
            key: AuthConfigKeys::REQUIRE_ACTIVATION_CODE,
            type: ParameterType::BOOL,
            default: true,
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $configApi->registerParameter(new ConfigParameterDefinition(
            domain: AuthConfigKeys::DOMAIN,
            key: AuthConfigKeys::NON_CONFIRMED_COMMENT_THRESHOLD,
            type: ParameterType::INT,
            default: 5,
            constraints: ['min' => 0],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));

        $configApi->registerParameter(new ConfigParameterDefinition(
            domain: AuthConfigKeys::DOMAIN,
            key: AuthConfigKeys::NON_CONFIRMED_TIMESPAN,
            type: ParameterType::TIME,
            default: 604800, // 7 days in seconds
            constraints: ['min' => 0],
            visibility: ConfigParameterVisibility::ALL_ADMINS,
        ));
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);
        $registry->registerPage(
            'auth.promotion_requests',
            'auth',
            __('auth::admin.promotion.nav_label'),
            AdminRegistryTarget::route('auth.admin.promotion-requests.index'),
            'upgrade',
            [Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR],
            4,
        );
    }
}
