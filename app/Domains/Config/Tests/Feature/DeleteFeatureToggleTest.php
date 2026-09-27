<?php

use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use App\Domains\Config\Public\Events\FeatureToggleDeleted;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn () => clearFeatureToggleDefinitions());

function makeOrphanToggle(TestCase $t, string $name, string $domain = 'config'): void
{
    createFeatureToggle($t, new FeatureToggle(
        name: $name,
        domain: $domain,
        admin_visibility: FeatureToggleAdminVisibility::ALL_ADMINS,
        access: FeatureToggleAccess::ON,
    ));
    clearFeatureToggleDefinitions();
}

describe('Feature toggles - deleteFeatureToggle', function () {
    it('deletes an orphan row as tech admin and emits FeatureToggleDeleted', function () {
        makeOrphanToggle($this, 'delete-orphan');
        $this->actingAs(techAdmin($this));

        app(ConfigPublicApi::class)->deleteFeatureToggle('DELETE-ORPHAN', 'Config');

        expect(DB::table('config_feature_toggles')->where('name', 'delete-orphan')->exists())->toBeFalse();

        $event = latestEventOf(FeatureToggleDeleted::name(), FeatureToggleDeleted::class);
        expect($event)->not->toBeNull();
        expect($event->featureToggle->name)->toBe('delete-orphan');
        expect($event->featureToggle->domain)->toBe('config');
        expect($event->featureToggle->admin_visibility)->toBe(FeatureToggleAdminVisibility::TECH_ADMINS_ONLY->value);
    });

    it('does nothing when there is no row', function () {
        $this->actingAs(techAdmin($this));

        app(ConfigPublicApi::class)->deleteFeatureToggle('delete-missing');

        expect(latestEventOf(FeatureToggleDeleted::name(), FeatureToggleDeleted::class))->toBeNull();
    });

    it('refuses to delete a declared toggle', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'delete-declared',
            domain: 'config',
            access: FeatureToggleAccess::ON,
        ));
        $this->actingAs(techAdmin($this));

        expect(fn () => app(ConfigPublicApi::class)->deleteFeatureToggle('delete-declared'))
            ->toThrow(DomainException::class);
        expect(DB::table('config_feature_toggles')->where('name', 'delete-declared')->exists())->toBeTrue();
        expect(checkToggleState('delete-declared'))->toBeTrue();
    });

    it('forbids an admin from deleting an orphan', function () {
        makeOrphanToggle($this, 'delete-forbidden');
        $this->actingAs(admin($this));

        expect(fn () => app(ConfigPublicApi::class)->deleteFeatureToggle('delete-forbidden'))
            ->toThrow(AuthorizationException::class);
        expect(DB::table('config_feature_toggles')->where('name', 'delete-forbidden')->exists())->toBeTrue();
    });
});
