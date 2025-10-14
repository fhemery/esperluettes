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

describe('Feature toggles', function () {
    describe('Creating a feature toggle', function () {
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
    });

    describe('Checking toggle state', function () {
        it('returns false for unknown toggle', function () {
            $api = app(ConfigPublicApi::class);

            expect($api->isToggleEnabled('unknown-toggle'))->toBeFalse();
        });

        it('returns false for unknown toggle in a domain', function () {
            createFeatureToggle($this, new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::ON,
            ));
            $api = app(ConfigPublicApi::class);

            expect($api->isToggleEnabled('test-feature', 'events'))->toBeFalse();
        });

        it('returns true for toggle with ON access', function () {
            $feature = new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::ON,
            );

            createFeatureToggle($this, $feature);

            expect(checkToggleState('test-feature'))->toBeTrue();
        });

        it('returns false for toggle with OFF access', function () {
            $feature = new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::OFF,
            );

            createFeatureToggle($this, $feature);

            expect(checkToggleState('test-feature'))->toBeFalse();
        });

        describe('Checking toggle state with ROLE_BASED access', function () {
            it('returns true if user has role', function () {
                $feature = new FeatureToggle(
                    name: 'test-feature',
                    domain: 'config',
                    access: FeatureToggleAccess::ROLE_BASED,
                    roles: [Roles::USER_CONFIRMED]
                );

                createFeatureToggle($this, $feature);

                $user = alice($this);
                $this->actingAs($user);

                expect(checkToggleState('test-feature'))->toBeTrue();
            });

            it('returns false if user does not have role', function () {
                $feature = new FeatureToggle(
                    name: 'test-feature',
                    domain: 'config',
                    access: FeatureToggleAccess::ROLE_BASED,
                    roles: [Roles::USER]
                );

                $user = alice($this);
                $this->actingAs($user);
                createFeatureToggle($this, $feature);

                expect(checkToggleState('test-feature'))->toBeFalse();
            });
        });
    });

    describe('Updating a toogle value', function () {
        it('should return nothing if toggle is not found', function() {
            $api = app(ConfigPublicApi::class);
            $this->actingAs(techAdmin($this));
            $api->updateFeatureToggle('test-feature', FeatureToggleAccess::OFF);

            // Did not throw. That's enough
        });

        it('should do nothing if toggle is not found in a domain', function() {
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
    });

    describe('Deleting a toggle', function () {
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
    });

    describe('Listing toggles', function () {
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
});
