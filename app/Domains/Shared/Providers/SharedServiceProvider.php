<?php

namespace App\Domains\Shared\Providers;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Shared\Contracts\ParameterType;
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
    public const TAB_GENERAL = 'general';
    public const SECTION_APPEARANCE = 'appearance';
    public const KEY_THEME = 'theme';
    public const KEY_FONT = 'font';

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

        // Register settings after all providers have booted
        $this->app->booted(function () {
            $this->registerSettings();
        });

        // Domain-specific breadcrumb builders are registered in their own ServiceProviders.
    }

    private function registerSettings(): void
    {
        $settingsApi = app(SettingsPublicApi::class);

        // Skip if already registered (idempotent for testing)
        if ($settingsApi->getTab(self::TAB_GENERAL) !== null) {
            return;
        }

        // Register "General" tab
        $settingsApi->registerTab(new SettingsTabDefinition(
            id: self::TAB_GENERAL,
            order: 10,
            nameKey: 'shared::settings.tabs.general',
            icon: 'settings',
        ));

        // Register "Appearance" section
        $settingsApi->registerSection(new SettingsSectionDefinition(
            tabId: self::TAB_GENERAL,
            id: self::SECTION_APPEARANCE,
            order: 10,
            nameKey: 'shared::settings.sections.appearance.name',
            descriptionKey: 'shared::settings.sections.appearance.description',
        ));

        // Register "Theme" parameter
        $settingsApi->registerParameter(new SettingsParameterDefinition(
            tabId: self::TAB_GENERAL,
            sectionId: self::SECTION_APPEARANCE,
            key: self::KEY_THEME,
            type: ParameterType::ENUM,
            default: 'seasonal',
            order: 10,
            nameKey: 'shared::settings.params.theme.name',
            descriptionKey: 'shared::settings.params.theme.description',
            constraints: [
                'options' => [
                    'seasonal' => 'shared::settings.params.theme.options.seasonal',
                    'autumn' => 'shared::settings.params.theme.options.autumn',
                    'winter' => 'shared::settings.params.theme.options.winter',
                ],
            ],
        ));

        // Register "Font" parameter
        $settingsApi->registerParameter(new SettingsParameterDefinition(
            tabId: self::TAB_GENERAL,
            sectionId: self::SECTION_APPEARANCE,
            key: self::KEY_FONT,
            type: ParameterType::ENUM,
            default: 'aptos',
            order: 20,
            nameKey: 'shared::settings.params.font.name',
            descriptionKey: 'shared::settings.params.font.description',
            constraints: [
                'options' => [
                    'aptos' => 'shared::settings.params.font.options.aptos',
                    'times' => 'shared::settings.params.font.options.times',
                ],
            ],
        ));
    }

    private function registerValidators(): void
    {
        CustomValidators::register();
    }
}
