<?php

use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use App\Domains\Config\Public\Events\FeatureToggleUpdated;
use App\Domains\Config\Public\Exceptions\UndeclaredFeatureToggleException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn () => clearFeatureToggleDefinitions());

describe('Feature toggles - updateFeatureToggle', function () {
    it('creates the row on the first change of a declared toggle', function () {
        declareFeatureToggle('update-first', 'config');
        $this->actingAs(techAdmin($this));

        app(ConfigPublicApi::class)->updateFeatureToggle('update-first', FeatureToggleAccess::ON, 'config');

        expect(DB::table('config_feature_toggles')->where('domain', 'config')->where('name', 'update-first')->count())->toBe(1);
        expect(checkToggleState('update-first'))->toBeTrue();
    });

    it('stores the row lowercased and matches case-insensitively', function () {
        declareFeatureToggle('update-case', 'config');
        $this->actingAs(techAdmin($this));
        $api = app(ConfigPublicApi::class);

        $api->updateFeatureToggle('UPDATE-CASE', FeatureToggleAccess::ON, 'CONFIG');
        $api->updateFeatureToggle('Update-Case', FeatureToggleAccess::OFF, 'Config');

        expect(DB::table('config_feature_toggles')->where('name', 'update-case')->where('domain', 'config')->count())->toBe(1);
        expect(checkToggleState('update-case'))->toBeFalse();
    });

    it('throws when the toggle is not declared', function () {
        $this->actingAs(techAdmin($this));

        expect(fn () => app(ConfigPublicApi::class)->updateFeatureToggle('update-undeclared', FeatureToggleAccess::ON))
            ->toThrow(UndeclaredFeatureToggleException::class);
        expect(DB::table('config_feature_toggles')->where('name', 'update-undeclared')->exists())->toBeFalse();
    });

    it('throws when the toggle is declared in another domain only', function () {
        declareFeatureToggle('update-other-domain', 'events');
        $this->actingAs(techAdmin($this));

        expect(fn () => app(ConfigPublicApi::class)->updateFeatureToggle('update-other-domain', FeatureToggleAccess::ON, 'config'))
            ->toThrow(UndeclaredFeatureToggleException::class);
    });

    it('sets roles when given and keeps them when roles is null', function () {
        declareFeatureToggle('update-roles', 'config');
        $this->actingAs(techAdmin($this));
        $api = app(ConfigPublicApi::class);

        $api->updateFeatureToggle('update-roles', FeatureToggleAccess::OFF, 'config');
        expect(json_decode(DB::table('config_feature_toggles')->where('name', 'update-roles')->value('roles'), true))->toBe([]);

        $api->updateFeatureToggle('update-roles', FeatureToggleAccess::ROLE_BASED, 'config', ['admin']);
        expect(json_decode(DB::table('config_feature_toggles')->where('name', 'update-roles')->value('roles'), true))->toBe(['admin']);

        $api->updateFeatureToggle('update-roles', FeatureToggleAccess::ON, 'config');
        $row = DB::table('config_feature_toggles')->where('name', 'update-roles')->first();
        expect($row->access)->toBe('on');
        expect(json_decode($row->roles, true))->toBe(['admin']);
    });

    it('lets an admin update an all_admins toggle', function () {
        declareFeatureToggle('update-all-admins', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);
        $this->actingAs(admin($this));

        app(ConfigPublicApi::class)->updateFeatureToggle('update-all-admins', FeatureToggleAccess::ON, 'config');

        expect(checkToggleState('update-all-admins'))->toBeTrue();
    });

    it('forbids an admin from updating a tech_admins_only toggle', function () {
        declareFeatureToggle('update-tech-only', 'config', FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
        DB::table('config_feature_toggles')->insert([
            'domain' => 'config',
            'name' => 'update-tech-only',
            'access' => 'on',
            'roles' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs(admin($this));

        expect(fn () => app(ConfigPublicApi::class)->updateFeatureToggle('update-tech-only', FeatureToggleAccess::OFF, 'config'))
            ->toThrow(AuthorizationException::class);
        expect(DB::table('config_feature_toggles')->where('name', 'update-tech-only')->value('access'))->toBe('on');
    });

    it('forbids a confirmed user', function () {
        declareFeatureToggle('update-user', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);
        $this->actingAs(alice($this));

        expect(fn () => app(ConfigPublicApi::class)->updateFeatureToggle('update-user', FeatureToggleAccess::ON, 'config'))
            ->toThrow(AuthorizationException::class);
        expect(DB::table('config_feature_toggles')->where('name', 'update-user')->exists())->toBeFalse();
    });

    it('emits FeatureToggleUpdated, including on row creation', function () {
        declareFeatureToggle('update-event', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);
        $this->actingAs(techAdmin($this));
        $api = app(ConfigPublicApi::class);

        $api->updateFeatureToggle('update-event', FeatureToggleAccess::ON, 'config');

        $event = latestEventOf(FeatureToggleUpdated::name(), FeatureToggleUpdated::class);
        expect($event)->not->toBeNull();
        expect($event->featureToggle->name)->toBe('update-event');
        expect($event->featureToggle->domain)->toBe('config');
        expect($event->featureToggle->access)->toBe(FeatureToggleAccess::ON->value);
        expect($event->featureToggle->admin_visibility)->toBe(FeatureToggleAdminVisibility::ALL_ADMINS->value);

        $api->updateFeatureToggle('update-event', FeatureToggleAccess::OFF, 'config');

        $event = latestEventOf(FeatureToggleUpdated::name(), FeatureToggleUpdated::class);
        expect($event->featureToggle->access)->toBe(FeatureToggleAccess::OFF->value);
    });

    it('lets createFeatureToggle set up a toggle through the declaration', function () {
        createFeatureToggle($this, new FeatureToggle(
            name: 'update-helper',
            domain: 'config',
            access: FeatureToggleAccess::ROLE_BASED,
            roles: ['admin'],
        ));

        $row = DB::table('config_feature_toggles')->where('name', 'update-helper')->first();
        expect($row->access)->toBe('role_based');
        expect(json_decode($row->roles, true))->toBe(['admin']);
    });
});
