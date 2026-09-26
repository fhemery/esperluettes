<?php

use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn () => clearFeatureToggleDefinitions());

describe('Feature toggles - listFeatureToggles', function () {
    it('lists a declared toggle without a row as OFF', function () {
        declareFeatureToggle('list-no-row', 'config');
        $this->actingAs(techAdmin($this));

        $toggles = app(ConfigPublicApi::class)->listFeatureToggles();

        expect($toggles)->toHaveCount(1);
        expect($toggles[0])->toBeInstanceOf(FeatureToggle::class);
        expect($toggles[0]->name)->toBe('list-no-row');
        expect($toggles[0]->domain)->toBe('config');
        expect($toggles[0]->access)->toBe(FeatureToggleAccess::OFF);
        expect($toggles[0]->roles)->toBe([]);
        expect($toggles[0]->admin_visibility)->toBe(FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
    });

    it('merges a declared toggle with its row, sorted by domain then name', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'list-b',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::ALL_ADMINS,
            access: FeatureToggleAccess::ROLE_BASED,
            roles: ['admin'],
        ));
        declareFeatureToggle('list-a', 'config');
        declareFeatureToggle('list-z', 'alpha');
        $this->actingAs(techAdmin($this));

        $toggles = app(ConfigPublicApi::class)->listFeatureToggles();

        expect(array_map(fn ($t) => $t->domain . '/' . $t->name, $toggles))
            ->toBe(['alpha/list-z', 'config/list-a', 'config/list-b']);
        expect($toggles[2]->access)->toBe(FeatureToggleAccess::ROLE_BASED);
        expect($toggles[2]->roles)->toBe(['admin']);
        expect($toggles[2]->admin_visibility)->toBe(FeatureToggleAdminVisibility::ALL_ADMINS);
    });

    it('does not list a row without declaration', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'list-orphan',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
        clearFeatureToggleDefinitions();
        $this->actingAs(techAdmin($this));

        expect(app(ConfigPublicApi::class)->listFeatureToggles())->toBeEmpty();
    });

    it('shows admins only all_admins declarations, tech admins all', function () {
        declareFeatureToggle('list-all-admins', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);
        declareFeatureToggle('list-tech-only', 'config', FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
        $api = app(ConfigPublicApi::class);

        $this->actingAs(admin($this));
        expect(array_map(fn ($t) => $t->name, $api->listFeatureToggles()))->toBe(['list-all-admins']);

        $this->actingAs(techAdmin($this));
        expect(array_map(fn ($t) => $t->name, $api->listFeatureToggles()))->toBe(['list-all-admins', 'list-tech-only']);
    });

    it('returns nothing to a non-admin', function () {
        declareFeatureToggle('list-user', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);
        $this->actingAs(alice($this));

        expect(app(ConfigPublicApi::class)->listFeatureToggles())->toBeEmpty();
    });
});

describe('Feature toggles - listOrphanFeatureToggles', function () {
    it('lists orphan rows to tech admins only', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'list-orphan-row',
            domain: 'config',
            admin_visibility: FeatureToggleAdminVisibility::ALL_ADMINS,
            access: FeatureToggleAccess::ON,
        ));
        clearFeatureToggleDefinitions();
        declareFeatureToggle('list-declared', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);
        $api = app(ConfigPublicApi::class);

        $this->actingAs(techAdmin($this));
        $orphans = $api->listOrphanFeatureToggles();
        expect($orphans)->toHaveCount(1);
        expect($orphans[0]->name)->toBe('list-orphan-row');
        expect($orphans[0]->domain)->toBe('config');
        expect($orphans[0]->access)->toBe(FeatureToggleAccess::ON);
        expect($orphans[0]->admin_visibility)->toBe(FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);

        $this->actingAs(admin($this));
        expect($api->listOrphanFeatureToggles())->toBe([]);

        $this->actingAs(alice($this));
        expect($api->listOrphanFeatureToggles())->toBe([]);
    });
});
