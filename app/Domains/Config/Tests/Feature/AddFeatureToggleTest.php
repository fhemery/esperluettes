<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use App\Domains\Config\Public\Events\FeatureToggleAdded;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Feature toggles - addFeatureToggle', function () {
    it('throws Unauthorized when creating a feature toggle as non tech admin', function () {
        $user = alice($this);
        $this->actingAs($user);

        $api = app(ConfigPublicApi::class);

        $feature = new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY,
            access: FeatureToggleAccess::ON,
            roles: []
        );

        $this->expectException(AuthorizationException::class);

        $api->addFeatureToggle($feature);
    });

    it('should proceed normally if user is tech admin', function () {
        $user = techAdmin($this);
        $this->actingAs($user);

        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));

        expect(checkToggleState('test-feature'))->toBeTrue();
    });

    it('should throw a ValidationException if name is empty or whitespace', function () {
        $user = techAdmin($this);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        createFeatureToggle($this, new FeatureToggle(
            name: '  ',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
    });

    it('should throw a ValidationException if domain is empty or whitespace', function () {
        $user = techAdmin($this);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: '  ',
            access: FeatureToggleAccess::ON,
        ));
    });

    it('should throw a ValidationException if toggle already exists', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));

        $this->expectException(ValidationException::class);

        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::OFF,
        ));
    });

    describe('Events', function () {
        it('should emit an event when a feature toggle is added', function () {
            $feature = new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::ON,
            );

            createFeatureToggle($this, $feature);

            $event = latestEventOf(FeatureToggleAdded::name(), FeatureToggleAdded::class);
            expect($event)->not->toBeNull();

            $snapshot = $event->featureToggle;
            expect($snapshot->name)->toBe('test-feature');
            expect($snapshot->domain)->toBe('config');
            expect($snapshot->access)->toBe('on');
            expect($snapshot->admin_visibility)->toBe('tech_admins_only');
            expect($snapshot->roles)->toBe([]);
        });
    });
});
