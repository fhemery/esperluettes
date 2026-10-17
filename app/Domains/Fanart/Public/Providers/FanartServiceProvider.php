<?php

namespace App\Domains\Fanart\Public\Providers;

use App\Domains\Fanart\Private\Support\FanartMediaUsageProvider;
use App\Domains\Fanart\Private\Support\FanartMode;
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

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Fanart/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/Fanart/Private/web.routes.php'));
        $this->loadRoutesFrom(app_path('Domains/Fanart/Private/api.routes.php'));
        $this->loadTranslationsFrom(app_path('Domains/Fanart/Private/Resources/lang'), 'fanart');
        $this->loadViewsFrom(app_path('Domains/Fanart/Private/Resources/views'), 'fanart');
        Blade::anonymousComponentPath(app_path('Domains/Fanart/Private/Resources/views/components'), 'fanart');
        Blade::componentNamespace('App\\Domains\\Fanart\\Private\\View\\Components', 'fanart');

        // Media skips the whole private `fanart/` root unless a provider claims files under it.
        app(MediaUsageRegistry::class)->register(new FanartMediaUsageProvider());

        $this->app->booted(fn () => $this->registerSettings());
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
