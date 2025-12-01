<?php

use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Admin User Controller', function () {
    describe('index', function () {
        it('displays the user list for admin users', function () {
            $admin = admin($this);
            $user = alice($this);

            $this->actingAs($admin);

            $response = $this->get(route('auth.admin.users.index'));

            $response->assertOk();
            $response->assertSee($user->email);
        });

        it('filters users by search term', function () {
            $admin = admin($this);
            $alice = alice($this);
            $bob = bob($this);

            $this->actingAs($admin);

            $response = $this->get(route('auth.admin.users.index', ['search' => 'alice']));

            $response->assertOk();
            $response->assertSee($alice->email);
            $response->assertDontSee($bob->email);
        });

        it('filters users by active status', function () {
            $admin = admin($this);
            $alice = alice($this);
            $bob = bob($this);
            $bob->update(['is_active' => false]);

            $this->actingAs($admin);

            $response = $this->get(route('auth.admin.users.index', ['is_active' => '1']));

            $response->assertOk();
            $response->assertSee($alice->email);
            $response->assertDontSee($bob->email);
        });

        it('denies access to non-admin users', function () {
            $user = alice($this);

            $this->actingAs($user);

            $response = $this->get(route('auth.admin.users.index'));

            $response->assertRedirect(route('dashboard'));
        });

        it('redirects to login for unauthenticated users', function () {
            $response = $this->get(route('auth.admin.users.index'));

            $response->assertRedirect(route('login'));
        });
    });

    describe('edit', function () {
        it('displays the edit form for a user', function () {
            $admin = admin($this);
            $user = alice($this);

            $this->actingAs($admin);

            $response = $this->get(route('auth.admin.users.edit', $user));

            $response->assertOk();
            $response->assertSee($user->email);
        });
    });

    describe('update', function () {
        it('updates user email', function () {
            $admin = admin($this);
            $user = alice($this);

            $this->actingAs($admin);

            $response = $this->put(route('auth.admin.users.update', $user), [
                'email' => 'new-email@example.com',
                'roles' => $user->roles->pluck('id')->toArray(),
            ]);

            $response->assertRedirect(route('auth.admin.users.index'));
            expect($user->fresh()->email)->toBe('new-email@example.com');
        });

        it('updates user roles', function () {
            $admin = admin($this);
            $user = alice($this);
            $moderatorRole = Role::where('slug', Roles::MODERATOR)->first() 
                ?? Role::create(['name' => 'Moderator', 'slug' => Roles::MODERATOR]);

            $this->actingAs($admin);

            $response = $this->put(route('auth.admin.users.update', $user), [
                'email' => $user->email,
                'roles' => [$moderatorRole->id],
            ]);

            $response->assertRedirect(route('auth.admin.users.index'));
            expect($user->fresh()->hasRole(Roles::MODERATOR))->toBeTrue();
        });

        it('validates email is required', function () {
            $admin = admin($this);
            $user = alice($this);

            $this->actingAs($admin);

            $response = $this->put(route('auth.admin.users.update', $user), [
                'email' => '',
            ]);

            $response->assertSessionHasErrors('email');
        });
    });

    describe('promote', function () {
        it('promotes a user from user to user-confirmed', function () {
            $admin = admin($this);
            $user = alice($this, roles: [Roles::USER]);

            $this->actingAs($admin);

            $response = $this->post(route('auth.admin.users.promote', $user));

            $response->assertRedirect();
            $user->refresh()->load('roles');
            expect($user->hasRole(Roles::USER_CONFIRMED))->toBeTrue();
            expect($user->hasRole(Roles::USER))->toBeFalse();
        });
    });

    describe('destroy', function () {
        it('deletes a user', function () {
            $admin = admin($this);
            $user = alice($this);
            $userId = $user->id;

            $this->actingAs($admin);

            $response = $this->delete(route('auth.admin.users.destroy', $user));

            $response->assertRedirect(route('auth.admin.users.index'));
            expect(User::find($userId))->toBeNull();
        });
    });

    describe('downloadAuthorization', function () {
        it('downloads authorization file when it exists', function () {
            Storage::fake('private');

            $admin = admin($this);
            $user = alice($this);
            $user->update([
                'is_under_15' => true,
                'parental_authorization_verified_at' => now(),
            ]);

            // Create the authorization file
            $fileName = 'authorization-' . $user->id . '.pdf';
            Storage::disk('private')->put('parental_authorizations/' . $fileName, 'PDF content');

            $this->actingAs($admin);

            $response = $this->get(route('auth.admin.users.download-authorization', $user));

            $response->assertOk();
            $response->assertDownload($fileName);
        });

        it('returns error when authorization file does not exist', function () {
            Storage::fake('private');

            $admin = admin($this);
            $user = alice($this);
            $user->update([
                'is_under_15' => true,
                'parental_authorization_verified_at' => now(),
            ]);

            $this->actingAs($admin);

            $response = $this->get(route('auth.admin.users.download-authorization', $user));

            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('clearAuthorization', function () {
        it('clears authorization for a minor user with authorization', function () {
            Storage::fake('private');

            $admin = admin($this);
            $user = alice($this);
            $user->update([
                'is_under_15' => true,
                'parental_authorization_verified_at' => now(),
            ]);

            // Create a fake authorization file
            $fileName = 'authorization-' . $user->id . '.pdf';
            Storage::disk('private')->put('parental_authorizations/' . $fileName, 'fake content');

            $this->actingAs($admin);

            $response = $this->post(route('auth.admin.users.clear-authorization', $user));

            $response->assertNoContent();
            expect(session('success'))->toBe(__('auth::admin.users.authorization.cleared'));
            expect($user->fresh()->parental_authorization_verified_at)->toBeNull();
            Storage::disk('private')->assertMissing('parental_authorizations/' . $fileName);
        });

        it('returns error when user is not a minor', function () {
            $admin = admin($this);
            $user = alice($this);
            $user->update(['is_under_15' => false]);

            $this->actingAs($admin);

            $response = $this->post(route('auth.admin.users.clear-authorization', $user));

            $response->assertRedirect();
            $response->assertSessionHas('error');
        });

        it('returns error when user has no authorization', function () {
            $admin = admin($this);
            $user = alice($this);
            $user->update([
                'is_under_15' => true,
                'parental_authorization_verified_at' => null,
            ]);

            $this->actingAs($admin);

            $response = $this->post(route('auth.admin.users.clear-authorization', $user));

            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('export', function () {
        it('exports users to CSV', function () {
            $admin = admin($this);
            alice($this);

            $this->actingAs($admin);

            $response = $this->get(route('auth.admin.users.export'));

            $response->assertOk();
            $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        });
    });
});
