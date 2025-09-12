<?php

namespace App\Domains\Shared\Providers;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Views\Layouts\AppLayout;
use App\Domains\Shared\Validation\CustomValidators;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings or services
    }

    public function boot(): void
    {
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
    }

    private function registerValidators(): void
    {
        CustomValidators::register();
    }
}
