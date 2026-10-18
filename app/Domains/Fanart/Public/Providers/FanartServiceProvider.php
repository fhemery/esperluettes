<?php

namespace App\Domains\Fanart\Public\Providers;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Support\FanartMediaUsageProvider;
use App\Domains\Fanart\Private\Support\FanartMode;
use App\Domains\Fanart\Public\Notifications\FanartAcceptedNotification;
use App\Domains\Fanart\Public\Notifications\FanartAudioReplacedNotification;
use App\Domains\Fanart\Public\Notifications\FanartDeclinedNotification;
use App\Domains\Fanart\Public\Notifications\FanartDeletedByModerationNotification;
use App\Domains\Fanart\Public\Notifications\FanartDeletedNotification;
use App\Domains\Fanart\Public\Notifications\FanartRemovedWithContentNotification;
use App\Domains\Fanart\Public\Notifications\FanartRoleDeclinedNotification;
use App\Domains\Fanart\Public\Notifications\FanartRoleGivenNotification;
use App\Domains\Fanart\Public\Notifications\FanartRoleRequestedNotification;
use App\Domains\Fanart\Public\Notifications\FanartSubmittedNotification;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FanartServiceProvider extends ServiceProvider
{
    public const TAB_STORIES = 'stories';
    public const TAB_PROFILE = 'profile';
    public const SECTION_FANART = 'fanart';
    public const SECTION_PRIVACY = 'privacy';
    public const KEY_IMAGES_MODE = 'fanart-images-mode';
    public const KEY_AUDIO_MODE = 'fanart-audio-mode';
    public const KEY_HIDE_FANART_TAB = 'hide-fanart-tab';

    public function register(): void
    {
        $this->app->singleton(FanartPolicy::class);

        // Loaded here, not in boot(): every register() runs before any boot(), so these routes are
        // matched before Story's `/stories/{slug}` catch-all (`.*`), which would swallow
        // `/stories/{slug}/fanarts/…`. Boot order cannot be changed: Fanart's settings
        // registration (in `booted`) must run after Story's.
        $this->loadRoutesFrom(app_path('Domains/Fanart/Private/web.routes.php'));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Fanart/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/Fanart/Private/api.routes.php'));
        $this->loadTranslationsFrom(app_path('Domains/Fanart/Private/Resources/lang'), 'fanart');
        $this->loadViewsFrom(app_path('Domains/Fanart/Private/Resources/views'), 'fanart');
        Blade::anonymousComponentPath(app_path('Domains/Fanart/Private/Resources/views/components'), 'fanart');
        Blade::componentNamespace('App\\Domains\\Fanart\\Private\\View\\Components', 'fanart');

        // Media skips the whole private `fanart/` root unless a provider claims files under it.
        app(MediaUsageRegistry::class)->register(new FanartMediaUsageProvider());

        $this->registerNotifications();

        $this->app->booted(fn () => $this->registerSettings());
    }

    private function registerNotifications(): void
    {
        $factory = app(NotificationFactory::class);

        $factory->registerGroup(
            id: 'fanart',
            sortOrder: 65,
            translationKey: 'fanart::notification.groups.fanart',
        );

        foreach ([
            FanartSubmittedNotification::class => 'submitted',
            FanartAcceptedNotification::class => 'accepted',
            FanartDeclinedNotification::class => 'declined',
            FanartDeletedNotification::class => 'deleted',
            FanartDeletedByModerationNotification::class => 'deleted_by_moderation',
            FanartAudioReplacedNotification::class => 'audio_replaced',
            FanartRemovedWithContentNotification::class => 'removed_with_content',
            FanartRoleRequestedNotification::class => 'role_requested',
            FanartRoleGivenNotification::class => 'role_given',
            FanartRoleDeclinedNotification::class => 'role_declined',
        ] as $class => $name) {
            $factory->register(
                type: $class::type(),
                class: $class,
                groupId: 'fanart',
                nameKey: "fanart::notification.settings.type_{$name}",
            );
        }
    }

    private function registerSettings(): void
    {
        $settingsApi = app(SettingsPublicApi::class);

        if ($settingsApi->getParameter(self::TAB_STORIES, self::KEY_IMAGES_MODE) === null) {
            $settingsApi->registerSection(new SettingsSectionDefinition(
                tabId: self::TAB_STORIES,
                id: self::SECTION_FANART,
                order: 20,
                nameKey: 'fanart::settings.sections.fanart.name',
                descriptionKey: 'fanart::settings.sections.fanart.description',
            ));

            foreach ([
                [self::KEY_IMAGES_MODE, FanartMode::Yes, 10, 'fanart-images-mode'],
                [self::KEY_AUDIO_MODE, FanartMode::Designated, 20, 'fanart-audio-mode'],
            ] as [$key, $default, $order, $name]) {
                $settingsApi->registerParameter(new SettingsParameterDefinition(
                    tabId: self::TAB_STORIES,
                    sectionId: self::SECTION_FANART,
                    key: $key,
                    type: ParameterType::ENUM,
                    default: $default->value,
                    order: $order,
                    nameKey: "fanart::settings.params.{$name}.name",
                    descriptionKey: "fanart::settings.params.{$name}.description",
                    constraints: [
                        'options' => [
                            'yes' => "fanart::settings.params.{$name}.options.yes",
                            'designated' => "fanart::settings.params.{$name}.options.designated",
                            'no' => "fanart::settings.params.{$name}.options.no",
                        ],
                    ],
                ));
            }
        }

        if ($settingsApi->getParameter(self::TAB_PROFILE, self::KEY_HIDE_FANART_TAB) === null) {
            $settingsApi->registerParameter(new SettingsParameterDefinition(
                tabId: self::TAB_PROFILE,
                sectionId: self::SECTION_PRIVACY,
                key: self::KEY_HIDE_FANART_TAB,
                type: ParameterType::BOOL,
                default: true,
                order: 40,
                nameKey: 'fanart::settings.params.hide-fanart-tab.name',
                descriptionKey: 'fanart::settings.params.hide-fanart-tab.description',
            ));
        }
    }
}
