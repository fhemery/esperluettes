<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Contracts\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use App\Domains\Config\Public\Events\FeatureToggleUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Feature toggles - updateFeatureToggle', function () {
    it('should return nothing if toggle is not found', function () {
        $api = app(ConfigPublicApi::class);
        $this->actingAs(techAdmin($this));
        $api->updateFeatureToggle('test-feature', FeatureToggleAccess::OFF);

        // Did not throw. That's enough
    });

    it('should do nothing if toggle is not found in a domain', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
        $api = app(ConfigPublicApi::class);
        $this->actingAs(techAdmin($this));
        $api->updateFeatureToggle('test-feature', FeatureToggleAccess::OFF, 'events');

        expect(checkToggleState('test-feature', 'config'))->toBeTrue();
    });

    it('throws Unauthorized when updating a TECH_ADMINS_ONLY feature toggle as non tech admin', function () {
        $feature = new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
            access: FeatureToggleAccess::ON,
        );
        createFeatureToggle($this, $feature);

        $user = admin($this);
        $this->actingAs($user);

        $api = app(ConfigPublicApi::class);

        $this->expectException(AuthorizationException::class);
        $api->updateFeatureToggle('test-feature', FeatureToggleAccess::OFF);
    });

    it('does update the value if user is tech admin', function () {
        $feature = new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
            access: FeatureToggleAccess::ON,
        );
        createFeatureToggle($this, $feature);

        $user = techAdmin($this);
        $this->actingAs($user);

        $api = app(ConfigPublicApi::class);
        $api->updateFeatureToggle('test-feature', FeatureToggleAccess::OFF);

        expect(checkToggleState('test-feature'))->toBeFalse();
    });

    it('does update the value if user is admin and feature toggle is admin allowed', function () {
        $feature = new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::ALL_ADMINS,
            access: FeatureToggleAccess::ON,
        );
        createFeatureToggle($this, $feature);

        $user = admin($this);
        $this->actingAs($user);

        $api = app(ConfigPublicApi::class);
        $api->updateFeatureToggle('test-feature', FeatureToggleAccess::OFF);

        expect(checkToggleState('test-feature'))->toBeFalse();
    });

    it('does work case insensitively', function () {
        $feature = new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
            access: FeatureToggleAccess::ON,
        );
        createFeatureToggle($this, $feature);

        $user = techAdmin($this);
        $this->actingAs($user);

        $api = app(ConfigPublicApi::class);
        $api->updateFeatureToggle('TEST-FEATURE', FeatureToggleAccess::OFF);

        expect(checkToggleState('test-feature'))->toBeFalse();
    });

    describe('Events', function () {
        it('should emit an event when a feature toggle is updated', function () {
            $feature = new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::ON,
            );
            createFeatureToggle($this, $feature);

            $user = techAdmin($this);
            $this->actingAs($user);
            $api = app(ConfigPublicApi::class);

            // Act
            $api->updateFeatureToggle('test-feature', FeatureToggleAccess::OFF, 'config');

            $event = latestEventOf(FeatureToggleUpdated::name(), FeatureToggleUpdated::class);
            expect($event)->not->toBeNull();

            $snapshot = $event->featureToggle;
            expect($snapshot->name)->toBe($feature->name);
            expect($snapshot->domain)->toBe($feature->domain);
            expect($snapshot->access)->toBe(FeatureToggleAccess::OFF->value);
        });
    });
});
