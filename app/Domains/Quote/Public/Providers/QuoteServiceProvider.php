<?php

namespace App\Domains\Quote\Public\Providers;

use App\Domains\Profile\Public\Api\ProfileTabRegistry;
use App\Domains\Profile\Public\Contracts\ProfileTabDefinition;
use App\Domains\Profile\Public\Contracts\ProfileTabPrivacy;
use App\Domains\Quote\Public\Visibility\QuotesTabVisibility;
use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Quote\Private\Listeners\NotifyAuthorsOnQuoteCreated;
use App\Domains\Quote\Private\Listeners\DeleteQuotesOnUserDeleted;
use App\Domains\Quote\Private\Listeners\RestoreOnUserReactivated;
use App\Domains\Quote\Private\Listeners\SoftDeleteOnUserDeactivated;
use App\Domains\Quote\Public\Events\ChapterPassageQuoted;
use App\Domains\Quote\Public\Notifications\ChapterQuotedNotification;
use App\Domains\Quote\Private\Services\QuotePolicy;
use App\Domains\Quote\Private\Services\QuoteService;
use App\Domains\Quote\Private\Support\QuoteNoteSanitizer;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class QuoteServiceProvider extends ServiceProvider
{
    public const KEY_HIDE_QUOTES_TAB = 'hide-quotes-tab';
    public const TAB_PROFILE = 'profile';
    public const SECTION_PRIVACY = 'privacy';

    public function register(): void
    {
        $this->mergeConfigFrom(app_path('Domains/Quote/Private/Config/quote.php'), 'quote');

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
        $this->loadViewsFrom(app_path('Domains/Quote/Private/Resources/views'), 'quote');
        Blade::anonymousComponentPath(app_path('Domains/Quote/Private/Resources/views/components'), 'quote');
        Blade::componentNamespace('App\\Domains\\Quote\\Private\\View\\Components', 'quote');

        app(ProfileTabRegistry::class)->register(new ProfileTabDefinition(
            key: 'quotes',
            order: 50,
            component: 'quote::profile-tab',
            labelKey: 'quote::ui.profile_tab.quotes',
            ownLabelKey: 'quote::ui.profile_tab.my_quotes',
            visibility: QuotesTabVisibility::class,
            privacy: new ProfileTabPrivacy(
                settingsTabId: self::TAB_PROFILE,
                settingsKey: self::KEY_HIDE_QUOTES_TAB,
            ),
        ));

        $eventBus = app(EventBus::class);
        $this->registerNotifications();
        $eventBus->subscribe(UserDeleted::class, [app(DeleteQuotesOnUserDeleted::class), 'handle']);
        $eventBus->subscribe(UserDeactivated::class, [app(SoftDeleteOnUserDeactivated::class), 'handle']);
        $eventBus->subscribe(UserReactivated::class, [app(RestoreOnUserReactivated::class), 'handle']);

        // After all providers boot so the Profile tab/section already exist
        $this->app->booted(function () {
            $this->registerSettings();
        });
    }

    private function registerNotifications(): void
    {
        $factory = app(NotificationFactory::class);

        $factory->registerGroup(
            id: 'quote',
            sortOrder: 60,
            translationKey: 'quote::notification.groups.quote',
        );

        $factory->register(
            type: ChapterQuotedNotification::type(),
            class: ChapterQuotedNotification::class,
            groupId: 'quote',
            nameKey: 'quote::notification.settings.type_chapter_quoted',
        );

        $eventBus = app(EventBus::class);
        $eventBus->subscribe(ChapterPassageQuoted::class, [app(NotifyAuthorsOnQuoteCreated::class), 'handle']);
    }

    private function registerSettings(): void
    {
        $settingsApi = app(SettingsPublicApi::class);

        if ($settingsApi->getParameter(self::TAB_PROFILE, self::KEY_HIDE_QUOTES_TAB) !== null) {
            return;
        }

        $settingsApi->registerParameter(new SettingsParameterDefinition(
            tabId: self::TAB_PROFILE,
            sectionId: self::SECTION_PRIVACY,
            key: self::KEY_HIDE_QUOTES_TAB,
            type: ParameterType::BOOL,
            default: false,
            order: 30,
            nameKey: 'quote::settings.params.hide-quotes-tab.name',
            descriptionKey: 'quote::settings.params.hide-quotes-tab.description',
        ));
    }
}
