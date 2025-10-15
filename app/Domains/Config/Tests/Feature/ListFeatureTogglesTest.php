<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Contracts\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Feature toggles - listFeatureToggles', function () {
    it('returns an empty array if no toggles are found', function () {
        $api = app(ConfigPublicApi::class);
        expect($api->listFeatureToggles())->toBeEmpty();
    });

    it('returns an array of toggles', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
        $api = app(ConfigPublicApi::class);
        $this->actingAs(techAdmin($this));
        $toggles = $api->listFeatureToggles();
        expect($toggles)->toBeArray();
        expect($toggles)->toHaveCount(1);
        expect($toggles[0])->toBeInstanceOf(FeatureToggle::class);
        expect($toggles[0]->name)->toBe('test-feature');
        expect($toggles[0]->domain)->toBe('config');
        expect($toggles[0]->access)->toBe(FeatureToggleAccess::ON);
        expect($toggles[0]->admin_visibility)->toBe(FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
        expect($toggles[0]->roles)->toBe([]);
    });

    it('should not list toggles for non admin users', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
        $api = app(ConfigPublicApi::class);
        $this->actingAs(alice($this));
        $toggles = $api->listFeatureToggles();
        expect($toggles)->toBeEmpty();
    });

    it('should list only toggles to admin users', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::ALL_ADMINS,
            access: FeatureToggleAccess::ON,
        ));

        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature-2',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
            access: FeatureToggleAccess::ON,
        ));
        $api = app(ConfigPublicApi::class);
        $this->actingAs(admin($this));
        $toggles = $api->listFeatureToggles();
        expect($toggles)->toBeArray();
        expect($toggles)->toHaveCount(1);
        expect($toggles[0])->toBeInstanceOf(FeatureToggle::class);
        expect($toggles[0]->name)->toBe('test-feature');
    });
});
