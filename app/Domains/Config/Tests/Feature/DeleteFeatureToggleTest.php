<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Contracts\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use App\Domains\Config\Public\Events\FeatureToggleDeleted;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Feature toggles - deleteFeatureToggle', function () {
    it('should do nothing if toggle is not found', function() {
        $api = app(ConfigPublicApi::class);
        $this->actingAs(techAdmin($this));
        $api->deleteFeatureToggle('test-feature');

        // Did not throw. That's enough
    });

    it('should do nothing if domain does not match', function() {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
        $api = app(ConfigPublicApi::class);
        $this->actingAs(techAdmin($this));
        $api->deleteFeatureToggle('test-feature', 'events');

        expect(checkToggleState('test-feature', 'config'))->toBeTrue();
    });

    it('throws Unauthorized when not done by a tech admin, event if feature toggle is admin allowed', function () {
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

        $this->expectException(AuthorizationException::class);
        $api->deleteFeatureToggle('test-feature');
    });

    it('does delete the toggle if user is tech admin', function () {
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
        $api->deleteFeatureToggle('test-feature');

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
        $api->deleteFeatureToggle('TEST-FEATURE');

        expect(checkToggleState('test-feature'))->toBeFalse();
    });

    describe('Events', function () {
        it('should emit an event when a feature toggle is deleted', function () {
            $feature = new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::ON,
            );
            createFeatureToggle($this, $feature);

            $user = techAdmin($this);
            $this->actingAs($user);
            $api = app(ConfigPublicApi::class);
            $api->deleteFeatureToggle('test-feature');

            $event = latestEventOf(FeatureToggleDeleted::name(), FeatureToggleDeleted::class);
            expect($event)->not->toBeNull();

            $snapshot = $event->featureToggle;
            expect($snapshot->name)->toBe($feature->name);
            expect($snapshot->domain)->toBe($feature->domain);
          
        });
    });
});