<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Feature toggles - isToggleEnabled', function () {
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

    it('should work case insensitively', function () {
        $feature = new FeatureToggle(
            name: 'test-feaTUre',
            domain: 'coNFig',
            access: FeatureToggleAccess::ON,
        );

        createFeatureToggle($this, $feature);

        expect(checkToggleState('TEST-FEATURE', 'config'))->toBeTrue();
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
