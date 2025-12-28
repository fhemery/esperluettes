<?php

use App\Domains\Settings\Private\Repositories\SettingRepository;
use App\Domains\Settings\Private\Services\SettingsRegistryService;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use Illuminate\Support\Facades\Cache;

function clearSettingsRegistry(): void
{
    SettingsRegistryService::clearAll();
}

function registerSettingsTab(SettingsTabDefinition $tab): void
{
    $api = app(SettingsPublicApi::class);
    $api->registerTab($tab);
}

function registerSettingsSection(SettingsSectionDefinition $section): void
{
    $api = app(SettingsPublicApi::class);
    $api->registerSection($section);
}

function registerSettingsParameter(SettingsParameterDefinition $param): void
{
    $api = app(SettingsPublicApi::class);
    $api->registerParameter($param);
}

function getSettingsValue(int $userId, string $tabId, string $key): mixed
{
    $api = app(SettingsPublicApi::class);

    return $api->getValue($userId, $tabId, $key);
}

function setSettingsValue(int $userId, string $tabId, string $key, mixed $value): void
{
    $api = app(SettingsPublicApi::class);
    $api->setValue($userId, $tabId, $key, $value);
}

function resetSettingsToDefault(int $userId, string $tabId, string $key): void
{
    $api = app(SettingsPublicApi::class);
    $api->resetToDefault($userId, $tabId, $key);
}

function clearSettingsCache(int $userId): void
{
    Cache::forget("user_settings:{$userId}");
}

/**
 * Helper to register a complete tab with section and parameter for testing.
 */
function registerTestSettingsStructure(
    string $tabId = 'test',
    string $sectionId = 'general',
    SettingsParameterDefinition $param = null
): void {
    registerSettingsTab(new SettingsTabDefinition(
        id: $tabId,
        order: 10,
        nameKey: 'test::tab.name',
    ));

    registerSettingsSection(new SettingsSectionDefinition(
        tabId: $tabId,
        id: $sectionId,
        order: 10,
        nameKey: 'test::section.name',
    ));

    if ($param) {
        registerSettingsParameter($param);
    }
}
