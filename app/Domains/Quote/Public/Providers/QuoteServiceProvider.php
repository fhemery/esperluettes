<?php

namespace App\Domains\Quote\Public\Providers;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Quote\Private\Listeners\NullifyUserOnUserDeleted;
use App\Domains\Quote\Private\Listeners\RestoreOnUserReactivated;
use App\Domains\Quote\Private\Listeners\SoftDeleteOnUserDeactivated;
use App\Domains\Quote\Private\Services\QuotePolicy;
use App\Domains\Quote\Private\Services\QuoteService;
use App\Domains\Quote\Private\Support\QuoteNoteSanitizer;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Support\ServiceProvider;

class QuoteServiceProvider extends ServiceProvider
{
    public const TAB_QUOTE = 'quote';
    public const SECTION_GENERAL = 'general';
    public const KEY_BOOK_PUBLIC = 'book_public';

    public function register(): void
    {
        $this->app->singleton(QuoteNoteSanitizer::class);
        $this->app->singleton(QuotePolicy::class);
        $this->app->singleton(QuoteService::class);
        $this->app->singleton(QuotePublicApi::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Quote/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/Quote/Private/routes.php'));
        $this->loadTranslationsFrom(app_path('Domains/Quote/Private/Resources/lang'), 'quote');

        $eventBus = app(EventBus::class);
        $this->registerSettings();
        $eventBus->subscribe(UserDeleted::class, [app(NullifyUserOnUserDeleted::class), 'handle']);
        $eventBus->subscribe(UserDeactivated::class, [app(SoftDeleteOnUserDeactivated::class), 'handle']);
        $eventBus->subscribe(UserReactivated::class, [app(RestoreOnUserReactivated::class), 'handle']);
    }

    private function registerSettings(): void
    {
        $settingsApi = app(SettingsPublicApi::class);

        if ($settingsApi->getTab(self::TAB_QUOTE) !== null) {
            return;
        }

        $settingsApi->registerTab(new SettingsTabDefinition(
            id: self::TAB_QUOTE,
            order: 30,
            nameKey: 'quote::settings.tabs.quote',
            icon: 'bookmark',
        ));

        $settingsApi->registerSection(new SettingsSectionDefinition(
            tabId: self::TAB_QUOTE,
            id: self::SECTION_GENERAL,
            order: 10,
            nameKey: 'quote::settings.sections.general.name',
            descriptionKey: 'quote::settings.sections.general.description',
        ));

        $settingsApi->registerParameter(new SettingsParameterDefinition(
            tabId: self::TAB_QUOTE,
            sectionId: self::SECTION_GENERAL,
            key: self::KEY_BOOK_PUBLIC,
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'quote::settings.params.book_public.name',
            descriptionKey: 'quote::settings.params.book_public.description',
        ));
    }
}
