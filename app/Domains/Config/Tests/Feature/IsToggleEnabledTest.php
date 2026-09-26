<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use App\Domains\Config\Public\Contracts\FeatureToggleDefinition;
use App\Domains\Config\Public\Exceptions\UndeclaredFeatureToggleException;
use App\Domains\Config\Public\Services\FeatureToggleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn () => clearFeatureToggleDefinitions());

describe('Feature toggles - isToggleEnabled', function () {
    it('throws when the toggle is not declared', function () {
        $api = app(ConfigPublicApi::class);

        expect(fn () => $api->isToggleEnabled('nope'))
            ->toThrow(UndeclaredFeatureToggleException::class, 'config/nope');
    });

    it('throws when the toggle is declared in another domain only', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
        $api = app(ConfigPublicApi::class);

        expect(fn () => $api->isToggleEnabled('test-feature', 'events'))
            ->toThrow(UndeclaredFeatureToggleException::class, 'events/test-feature');
    });

    it('returns false for a declared toggle without a row', function () {
        declareFeatureToggle('test-feature');

        expect(checkToggleState('test-feature'))->toBeFalse();
    });

    it('returns true for a declared toggle with ON access', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));

        expect(checkToggleState('test-feature'))->toBeTrue();
    });

    it('returns false for a declared toggle with OFF access', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feature',
            domain: 'config',
            access: FeatureToggleAccess::OFF,
        ));

        expect(checkToggleState('test-feature'))->toBeFalse();
    });

    it('is case-insensitive on domain and name', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'test-feaTUre',
            domain: 'coNFig',
            access: FeatureToggleAccess::ON,
        ));

        expect(app(ConfigPublicApi::class)->isToggleEnabled('TEST-FEATURE', 'CONFIG'))->toBeTrue();
    });

    describe('Checking toggle state with ROLE_BASED access', function () {
        it('returns true if user has role', function () {
            createFeatureToggle($this, new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::ROLE_BASED,
                roles: [Roles::USER_CONFIRMED]
            ));

            $this->actingAs(alice($this));

            expect(checkToggleState('test-feature'))->toBeTrue();
        });

        it('returns false if user does not have role', function () {
            createFeatureToggle($this, new FeatureToggle(
                name: 'test-feature',
                domain: 'config',
                access: FeatureToggleAccess::ROLE_BASED,
                roles: [Roles::USER]
            ));

            $this->actingAs(alice($this));

            expect(checkToggleState('test-feature'))->toBeFalse();
        });
    });

    it('keeps the last declaration when a toggle is declared twice', function () {
        declareFeatureToggle('test-feature');
        declareFeatureToggle('TEST-feature', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

        $definition = app(FeatureToggleService::class)->getDefinition('test-feature');

        expect($definition)->toBeInstanceOf(FeatureToggleDefinition::class)
            ->and($definition->name)->toBe('TEST-feature')
            ->and($definition->adminVisibility)->toBe(FeatureToggleAdminVisibility::ALL_ADMINS)
            ->and(checkToggleState('test-feature'))->toBeFalse();
    });
});
