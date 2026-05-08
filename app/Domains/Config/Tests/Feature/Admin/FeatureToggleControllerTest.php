<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Private\Models\FeatureToggle;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Config\Public\Contracts\FeatureToggle as FeatureToggleContract;
use App\Domains\Config\Public\Contracts\FeatureToggleAccess;
use App\Domains\Config\Public\Contracts\FeatureToggleAdminVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeToggle(string $name = 'test-toggle', string $domain = 'config', FeatureToggleAccess $access = FeatureToggleAccess::OFF, FeatureToggleAdminVisibility $visibility = FeatureToggleAdminVisibility::ALL_ADMINS): FeatureToggle
{
    return FeatureToggle::create([
        'name'             => $name,
        'domain'           => $domain,
        'access'           => $access->value,
        'admin_visibility' => $visibility->value,
        'roles'            => [],
    ]);
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

        it('allows admin to view the list', function () {
            makeToggle(visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSee('test-toggle');
        });

        it('allows tech-admin to view the list', function () {
            makeToggle();

            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSee('test-toggle');
        });

        it('hides tech-admins-only toggles from regular admins', function () {
            makeToggle('hidden-toggle', visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
            makeToggle('visible-toggle', visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSee('visible-toggle')
                ->assertDontSee('hidden-toggle');
        });

        it('shows all toggles to tech-admins', function () {
            makeToggle('hidden-toggle', visibility: FeatureToggleAdminVisibility::TECH_ADMINS_ONLY);
            makeToggle('visible-toggle', visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.index'))
                ->assertOk()
                ->assertSee('hidden-toggle')
                ->assertSee('visible-toggle');
        });
    });

    describe('create', function () {
        it('redirects unauthenticated users', function () {
            $this->get(route('config.admin.feature-toggles.create'))
                ->assertRedirect(route('login'));
        });

        it('returns 403 for regular admin', function () {
            $this->actingAs(admin($this))
                ->get(route('config.admin.feature-toggles.create'))
                ->assertForbidden();
        });

        it('displays the create form for tech-admin', function () {
            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.create'))
                ->assertOk();
        });
    });

    describe('store', function () {
        it('returns 403 for regular admin', function () {
            $this->actingAs(admin($this))
                ->post(route('config.admin.feature-toggles.store'), [
                    'name'             => 'new-toggle',
                    'domain'           => 'config',
                    'admin_visibility' => 'all_admins',
                    'access'           => 'off',
                ])
                ->assertForbidden();
        });

        it('creates a toggle as tech-admin', function () {
            $this->actingAs(techAdmin($this))
                ->post(route('config.admin.feature-toggles.store'), [
                    'name'             => 'new-toggle',
                    'domain'           => 'config',
                    'admin_visibility' => 'all_admins',
                    'access'           => 'on',
                    'roles'            => [],
                ])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            $this->assertDatabaseHas('config_feature_toggles', [
                'name'   => 'new-toggle',
                'domain' => 'config',
                'access' => 'on',
            ]);
        });

        it('validates required fields', function () {
            $this->actingAs(techAdmin($this))
                ->post(route('config.admin.feature-toggles.store'), [])
                ->assertSessionHasErrors(['name', 'domain', 'admin_visibility', 'access']);
        });
    });

    describe('edit', function () {
        it('returns 403 for regular admin', function () {
            $toggle = makeToggle(visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->get(route('config.admin.feature-toggles.edit', $toggle))
                ->assertForbidden();
        });

        it('displays the edit form for tech-admin', function () {
            $toggle = makeToggle('my-toggle');

            $this->actingAs(techAdmin($this))
                ->get(route('config.admin.feature-toggles.edit', $toggle))
                ->assertOk()
                ->assertSee('my-toggle');
        });
    });

    describe('update', function () {
        it('returns 403 for regular admin', function () {
            $toggle = makeToggle(visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->put(route('config.admin.feature-toggles.update', $toggle), [
                    'admin_visibility' => 'all_admins',
                    'access'           => 'on',
                    'roles'            => [],
                ])
                ->assertForbidden();
        });

        it('updates a toggle as tech-admin', function () {
            $toggle = makeToggle(access: FeatureToggleAccess::OFF);

            $this->actingAs(techAdmin($this))
                ->put(route('config.admin.feature-toggles.update', $toggle), [
                    'admin_visibility' => 'all_admins',
                    'access'           => 'on',
                    'roles'            => [],
                ])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            $this->assertDatabaseHas('config_feature_toggles', [
                'id'     => $toggle->id,
                'access' => 'on',
            ]);
        });
    });

    describe('destroy', function () {
        it('returns 403 for regular admin', function () {
            $toggle = makeToggle(visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->delete(route('config.admin.feature-toggles.destroy', $toggle))
                ->assertForbidden();
        });

        it('deletes a toggle as tech-admin', function () {
            $toggle = makeToggle();

            $this->actingAs(techAdmin($this))
                ->delete(route('config.admin.feature-toggles.destroy', $toggle))
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            $this->assertDatabaseMissing('config_feature_toggles', ['id' => $toggle->id]);
        });
    });

    describe('setAccess', function () {
        it('redirects unauthenticated users', function () {
            $toggle = makeToggle(visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->post(route('config.admin.feature-toggles.setAccess', $toggle), ['access' => 'on'])
                ->assertRedirect(route('login'));
        });

        it('allows admin to set access on an all_admins toggle', function () {
            $toggle = makeToggle(access: FeatureToggleAccess::OFF, visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->post(route('config.admin.feature-toggles.setAccess', $toggle), ['access' => 'on'])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            $this->assertDatabaseHas('config_feature_toggles', [
                'id'     => $toggle->id,
                'access' => 'on',
            ]);
        });

        it('allows tech-admin to set access', function () {
            $toggle = makeToggle(access: FeatureToggleAccess::ON);

            $this->actingAs(techAdmin($this))
                ->post(route('config.admin.feature-toggles.setAccess', $toggle), ['access' => 'off'])
                ->assertRedirect(route('config.admin.feature-toggles.index'));

            $this->assertDatabaseHas('config_feature_toggles', [
                'id'     => $toggle->id,
                'access' => 'off',
            ]);
        });

        it('validates the access value', function () {
            $toggle = makeToggle(visibility: FeatureToggleAdminVisibility::ALL_ADMINS);

            $this->actingAs(admin($this))
                ->post(route('config.admin.feature-toggles.setAccess', $toggle), ['access' => 'invalid'])
                ->assertSessionHasErrors(['access']);
        });
    });
});
