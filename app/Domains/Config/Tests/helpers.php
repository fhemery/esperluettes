<?php

use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Api\ConfigPublicApi;
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