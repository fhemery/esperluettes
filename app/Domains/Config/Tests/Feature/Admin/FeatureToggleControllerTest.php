<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(fn () => clearFeatureToggleDefinitions());

/**
 * Insert a toggle row directly (no declaration involved).
 */
function insertToggleRow(string $name, string $domain = 'config', FeatureToggleAccess $access = FeatureToggleAccess::OFF, array $roles = []): int
{
    return DB::table('config_feature_toggles')->insertGetId([
        'name'       => $name,
        'domain'     => $domain,
        'access'     => $access->value,
        'roles'      => json_encode($roles),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function toggleAccess(string $name, string $domain = 'config'): ?string
{
    return DB::table('config_feature_toggles')->where('domain', $domain)->where('name', $name)->value('access');
}

function toggleRoute(string $route, string $name, string $domain = 'config'): string
{
    return route('config.admin.feature-toggles.'.$route, ['domain' => $domain, 'name' => $name]);
}

describe('FeatureToggle Admin Controller', function () {

    describe('index', function () {
        it('redirects unauthenticated users to login', function () {
            $this->get(route('config.admin.feature-toggles.index'))
                ->assertRedirect(route('login'));
        });

        it('denies access to non-admins', function () {
            $this->actingAs(alice($this, [], true, [Roles::USER_CONFIRMED]))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertRedirect();
        });

        it('shows a declared toggle without a row as OFF', function () {
            declareFeatureToggle('ctl-no-row', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSeeInOrder(['ctl-no-row', 'config::admin.feature_toggles.access.off']);

            expect(DB::table('config_feature_toggles')->where('name', 'ctl-no-row')->exists())->toBeFalse();
        });

        it('shows every declared toggle to tech admins', function () {
            declareFeatureToggle('ctl-tech-only', 'config', FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
            declareFeatureToggle('ctl-all-admins', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSee('ctl-tech-only')
                ->assertSee('ctl-all-admins');
        });

        it('hides tech_admins_only declarations from regular admins', function () {
            declareFeatureToggle('ctl-tech-only', 'config', FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
            declareFeatureToggle('ctl-all-admins', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSee('ctl-all-admins')
                ->assertDontSee('ctl-tech-only');
        });

        it('shows orphan rows with « Non déclaré dans le code » to tech admins', function () {
            insertToggleRow('ctl-orphan', 'legacy', FeatureToggleAccess::ON);

            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSee('config::admin.feature_toggles.orphans.title')
                ->assertSee('config::admin.feature_toggles.orphans.label')
                ->assertSee('ctl-orphan')
                ->assertSee(toggleRoute('destroy', 'ctl-orphan', 'legacy'), false)
                ->assertDontSee(toggleRoute('setAccess', 'ctl-orphan', 'legacy'), false)
                ->assertDontSee(toggleRoute('edit', 'ctl-orphan', 'legacy'), false);
        });

        it('hides the orphan section when there is no orphan', function () {
            declareFeatureToggle('ctl-declared', 'config');

            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertDontSee('config::admin.feature_toggles.orphans.title');
        });

        it('does not show orphan rows to regular admins', function () {
            insertToggleRow('ctl-orphan', 'legacy', FeatureToggleAccess::ON);

            $this->actingAs(admin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertDontSee('config::admin.feature_toggles.orphans.title')
                ->assertDontSee('ctl-orphan');
        });

        it('no longer offers a create button', function () {
            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertDontSee('/admin/config/feature-toggles/create', false);
        });
    });

    describe('create', function () {
        it('has no create route any more', function () {
            $this->actingAs(techAdmin($this))
                ->get('/admin/config/feature-toggles/create')
                ->assertNotFound();
        });
    });

    describe('setAccess', function () {
        it('redirects unauthenticated users', function () {
            declareFeatureToggle('ctl-set', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->post(toggleRoute('setAccess', 'ctl-set'), ['access' => 'on'])
                ->assertRedirect(route('login'));
        });

        it('creates the row when an admin sets access on a row-less all_admins toggle', function () {
            declareFeatureToggle('ctl-set', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->post(toggleRoute('setAccess', 'ctl-set'), ['access' => 'on'])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            expect(toggleAccess('ctl-set'))->toBe('on');
        });

        it('lets a tech admin set access on a tech_admins_only toggle', function () {
            declareFeatureToggle('ctl-set-tech', 'config', FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
            insertToggleRow('ctl-set-tech', 'config', FeatureToggleAccess::ON);

            $this->actingAs(techAdmin($this))
                ->post(toggleRoute('setAccess', 'ctl-set-tech'), ['access' => 'off'])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            expect(toggleAccess('ctl-set-tech'))->toBe('off');
        });

        it('returns 403 to an admin on a tech_admins_only toggle', function () {
            declareFeatureToggle('ctl-set-tech', 'config', FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
            insertToggleRow('ctl-set-tech', 'config', FeatureToggleAccess::ON);

            $this->actingAs(admin($this))
                ->post(toggleRoute('setAccess', 'ctl-set-tech'), ['access' => 'off'])
                ->assertForbidden();

            expect(toggleAccess('ctl-set-tech'))->toBe('on');
        });

        it('returns 404 for an undeclared toggle', function () {
            insertToggleRow('ctl-set-orphan', 'config', FeatureToggleAccess::ON);

            $this->actingAs(techAdmin($this))
                ->post(toggleRoute('setAccess', 'ctl-set-orphan'), ['access' => 'off'])
                ->assertNotFound();

            expect(toggleAccess('ctl-set-orphan'))->toBe('on');
        });

        it('validates the access value', function () {
            declareFeatureToggle('ctl-set', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->post(toggleRoute('setAccess', 'ctl-set'), ['access' => 'invalid'])
                ->assertSessionHasErrors(['access']);

            expect(toggleAccess('ctl-set'))->toBeNull();
        });
    });

    describe('edit', function () {
        it('returns 403 for regular admin', function () {
            declareFeatureToggle('ctl-edit', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->get(toggleRoute('edit', 'ctl-edit'))
                ->assertForbidden();
        });

        it('displays the edit form with access and roles only for tech admin', function () {
            declareFeatureToggle('ctl-edit', 'config');

            $this->actingAs(techAdmin($this))
                ->get(toggleRoute('edit', 'ctl-edit'))
                ->assertOk()
                ->assertSee('ctl-edit')
                ->assertSee('name="access"', false)
                ->assertDontSee('name="admin_visibility"', false);
        });

        it('returns 404 for an undeclared toggle', function () {
            insertToggleRow('ctl-edit-orphan');

            $this->actingAs(techAdmin($this))
                ->get(toggleRoute('edit', 'ctl-edit-orphan'))
                ->assertNotFound();
        });
    });

    describe('update', function () {
        it('returns 403 for regular admin', function () {
            declareFeatureToggle('ctl-update', 'config', FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->put(toggleRoute('update', 'ctl-update'), ['access' => 'on', 'roles' => []])
                ->assertForbidden();

            expect(toggleAccess('ctl-update'))->toBeNull();
        });

        it('updates access and roles as tech admin', function () {
            declareFeatureToggle('ctl-update', 'config');
            insertToggleRow('ctl-update', 'config', FeatureToggleAccess::OFF);

            $this->actingAs(techAdmin($this))
                ->put(toggleRoute('update', 'ctl-update'), ['access' => 'role_based', 'roles' => [Roles::ADMIN]])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            $row = DB::table('config_feature_toggles')->where('name', 'ctl-update')->first();
            expect($row->access)->toBe('role_based');
            expect(json_decode($row->roles, true))->toBe([Roles::ADMIN]);
        });

        it('creates the row of a row-less declared toggle', function () {
            declareFeatureToggle('ctl-update', 'config');

            $this->actingAs(techAdmin($this))
                ->put(toggleRoute('update', 'ctl-update'), ['access' => 'on'])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            expect(toggleAccess('ctl-update'))->toBe('on');
        });

        it('returns 404 for an undeclared toggle', function () {
            $this->actingAs(techAdmin($this))
                ->put(toggleRoute('update', 'ctl-update-undeclared'), ['access' => 'on'])
                ->assertNotFound();

            expect(toggleAccess('ctl-update-undeclared'))->toBeNull();
        });
    });

    describe('destroy', function () {
        it('deletes an orphan as tech admin', function () {
            insertToggleRow('ctl-orphan', 'legacy');

            $this->actingAs(techAdmin($this))
                ->delete(toggleRoute('destroy', 'ctl-orphan', 'legacy'))
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            expect(DB::table('config_feature_toggles')->where('name', 'ctl-orphan')->exists())->toBeFalse();
        });

        it('refuses to delete a declared toggle', function () {
            declareFeatureToggle('ctl-declared', 'config');
            insertToggleRow('ctl-declared');

            $this->actingAs(techAdmin($this))
                ->from(route('config.admin.feature-toggles.index'))
                ->delete(toggleRoute('destroy', 'ctl-declared'))
                ->assertRedirect(route('config.admin.feature-toggles.index'))
                ->assertSessionHas('error');

            expect(DB::table('config_feature_toggles')->where('name', 'ctl-declared')->exists())->toBeTrue();
        });

        it('returns 403 for regular admin', function () {
            insertToggleRow('ctl-orphan', 'legacy');

            $this->actingAs(admin($this))
                ->delete(toggleRoute('destroy', 'ctl-orphan', 'legacy'))
                ->assertForbidden();

            expect(DB::table('config_feature_toggles')->where('name', 'ctl-orphan')->exists())->toBeTrue();
        });
    });

    describe('schema', function () {
        it('no longer has an admin_visibility column', function () {
            expect(Schema::hasColumn('config_feature_toggles', 'admin_visibility'))->toBeFalse();
        });
    });
});
