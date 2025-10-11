<?php

namespace App\Domains\Shared\Providers;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Views\Layouts\AppLayout;
use App\Domains\Shared\Validation\CustomValidators;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Note: CSS and JS assets are located in app/Domains/Shared/Resources/
        // and are managed by Vite configuration (see vite.config.js)

        // Register domain-specific migrations (infrastructure/framework tables)
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Register language files
        $this->loadJsonTranslationsFrom(
            __DIR__ . '/../Resources/lang',
            'fr'
        );

        // Register PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'shared');


        // Register the view namespaces
        $this->loadViewsFrom(
            app_path('Domains/Shared/Resources/views'),
            'shared'
        );

        // Register anonymous components, both unprefixed (<x-editor>) and prefixed (<x-shared::editor>)
        Blade::anonymousComponentPath(app_path('Domains/Shared/Resources/views/components'));
        Blade::anonymousComponentPath(app_path('Domains/Shared/Resources/views/components'), 'shared');

        // Register class-based components under the 'shared' prefix
        Blade::componentNamespace('App\\Domains\\Shared\\Views\\Components', 'shared');

        // Register vendor notification views
        $this->loadViewsFrom(
            app_path('Domains/Shared/Views'),
            'vendor/notifications'
        );

        // Register the layout components
        Blade::component('app-layout', AppLayout::class);

        // Also register with the shared namespace for explicit usage
        Blade::component('shared::app-layout', AppLayout::class);

        // Share current profile DTO to navigation layout via contract
        View::composer('shared::layouts.partials.navigation', function ($view) {
            $dto = null;
            if (Auth::check()) {
                /** @var ProfilePublicApi $api */
                $api = app(ProfilePublicApi::class);
                $dto = $api->getPublicProfile(Auth::id());
            }
            $view->with('currentProfile', $dto);
        });

        // Safety guard: never run tests against a non-SQLite database
        if (app()->environment('testing') && config('database.default') !== 'sqlite') {
            throw new \RuntimeException('Tests must use sqlite. Clear config cache before running tests.');
        }

        // Blade directive: compact number formatter
        Blade::directive('compactNumber', function ($expression) {
            return "<?php echo \\App\\Domains\\Shared\\Support\\NumberFormatter::compact($expression); ?>";
        });

        // Register custom validators in a dedicated place
        $this->registerValidators();

        // Domain-specific breadcrumb builders are registered in their own ServiceProviders.
    }

    private function registerValidators(): void
    {
        CustomValidators::register();
    }
}
