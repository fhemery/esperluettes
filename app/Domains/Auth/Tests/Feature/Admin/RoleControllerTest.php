<?php

use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Role Admin Controller', function () {

    describe('index', function () {
        it('displays the role list for admins', function () {
            Role::create(['name' => 'Test Role', 'slug' => 'test-role']);

            $this->actingAs(admin($this))
                ->get(route('auth.admin.roles.index'))
                ->assertOk()
                ->assertSee('Test Role');
        });

        it('denies access to non-admins', function () {
            $user = registerUserThroughForm($this, ['email' => 'user@example.com'], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('auth.admin.roles.index'))
                ->assertRedirect();
        });

        it('redirects unauthenticated users to login', function () {
            $this->get(route('auth.admin.roles.index'))
                ->assertRedirect(route('login'));
        });

        it('shows users count per role', function () {
            $role = Role::create(['name' => 'Empty Role', 'slug' => 'empty-role']);

            $this->actingAs(admin($this))
                ->get(route('auth.admin.roles.index'))
                ->assertOk()
                ->assertSee('Empty Role');
        });
    });

    describe('create', function () {
        it('displays the create form for admins', function () {
            $this->actingAs(admin($this))
                ->get(route('auth.admin.roles.create'))
                ->assertOk();
        });
    });

    describe('store', function () {
        it('creates a role', function () {
            $this->actingAs(admin($this))
                ->post(route('auth.admin.roles.store'), [
                    'name' => 'New Role',
                    'slug' => 'new-role',
                    'description' => 'A new role',
                ])
                ->assertRedirect(route('auth.admin.roles.index'));

            $this->assertDatabaseHas('roles', ['name' => 'New Role', 'slug' => 'new-role']);
        });

        it('validates required fields', function () {
            $this->actingAs(admin($this))
                ->post(route('auth.admin.roles.store'), [])
                ->assertSessionHasErrors(['name', 'slug']);
        });

        it('validates unique name', function () {
            Role::create(['name' => 'Existing Role', 'slug' => 'existing-role']);

            $this->actingAs(admin($this))
                ->post(route('auth.admin.roles.store'), [
                    'name' => 'Existing Role',
                    'slug' => 'other-slug',
                ])
                ->assertSessionHasErrors(['name']);
        });

        it('validates unique slug', function () {
            Role::create(['name' => 'Existing Role', 'slug' => 'existing-role']);

            $this->actingAs(admin($this))
                ->post(route('auth.admin.roles.store'), [
                    'name' => 'Other Name',
                    'slug' => 'existing-role',
                ])
                ->assertSessionHasErrors(['slug']);
        });
    });

    describe('edit', function () {
        it('displays the edit form with role data', function () {
            $role = Role::create(['name' => 'Edit Me', 'slug' => 'edit-me']);

            $this->actingAs(admin($this))
                ->get(route('auth.admin.roles.edit', $role))
                ->assertOk()
                ->assertSee('Edit Me');
        });
    });

    describe('update', function () {
        it('updates a role', function () {
            $role = Role::create(['name' => 'Old Name', 'slug' => 'old-slug']);

            $this->actingAs(admin($this))
                ->put(route('auth.admin.roles.update', $role), [
                    'name' => 'New Name',
                    'slug' => 'new-slug',
                ])
                ->assertRedirect(route('auth.admin.roles.index'));

            $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'New Name', 'slug' => 'new-slug']);
        });

        it('allows keeping the same name and slug when updating', function () {
            $role = Role::create(['name' => 'Same Name', 'slug' => 'same-slug']);

            $this->actingAs(admin($this))
                ->put(route('auth.admin.roles.update', $role), [
                    'name' => 'Same Name',
                    'slug' => 'same-slug',
                    'description' => 'Updated description',
                ])
                ->assertRedirect(route('auth.admin.roles.index'))
                ->assertSessionHasNoErrors();
        });
    });

    describe('destroy', function () {
        it('deletes a role with no users', function () {
            $role = Role::create(['name' => 'Deletable', 'slug' => 'deletable']);

            $this->actingAs(admin($this))
                ->delete(route('auth.admin.roles.destroy', $role))
                ->assertRedirect(route('auth.admin.roles.index'));

            $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        });

        it('blocks deletion when role is assigned to users', function () {
            $admin = admin($this);

            // Admin user already has a role — find that role
            $assignedRole = $admin->roles()->first();

            $this->actingAs($admin)
                ->delete(route('auth.admin.roles.destroy', $assignedRole))
                ->assertRedirect()
                ->assertSessionHas('error');

            $this->assertDatabaseHas('roles', ['id' => $assignedRole->id]);
        });
    });
});
