<?php

use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\ConfigParameterDefinition;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Services\ConfigParameterService;
use Tests\TestCase;

function createFeatureToggle(TestCase $t, FeatureToggle $featureToggle): FeatureToggle
{
    $api = app(ConfigPublicApi::class);
    $t->actingAs(techAdmin($t));
    $api->addFeatureToggle($featureToggle);
    return $featureToggle;
}

function checkToggleState(string $featureToggleName): bool
{
    $api = app(ConfigPublicApi::class);
    return $api->isToggleEnabled($featureToggleName);
}

function registerParameter(ConfigParameterDefinition $definition): void
{
    $api = app(ConfigPublicApi::class);
    $api->registerParameter($definition);
}

function getParameterValue(string $key, string $domain): mixed
{
    $api = app(ConfigPublicApi::class);
    return $api->getParameterValue($key, $domain);
}

function clearParameterDefinitions(): void
{
    ConfigParameterService::clearDefinitions();
}