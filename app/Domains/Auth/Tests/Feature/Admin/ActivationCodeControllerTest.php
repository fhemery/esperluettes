<?php

use App\Domains\Auth\Private\Models\ActivationCode;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ActivationCode Admin Controller', function () {

    describe('index', function () {
        it('displays the list for admins', function () {
            ActivationCode::create([
                'code' => 'TEST-ABCDEFGH-1234',
                'sponsor_user_id' => null,
                'comment' => null,
                'expires_at' => null,
            ]);

            $this->actingAs(admin($this))
                ->get(route('auth.admin.activation-codes.index'))
                ->assertOk()
                ->assertSee('TEST-ABCDEFGH-1234');
        });

        it('denies access to non-admins', function () {
            $user = registerUserThroughForm($this, ['email' => 'user@example.com'], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('auth.admin.activation-codes.index'))
                ->assertRedirect();
        });

        it('redirects unauthenticated users to login', function () {
            $this->get(route('auth.admin.activation-codes.index'))
                ->assertRedirect(route('login'));
        });

        it('filters by code', function () {
            ActivationCode::create(['code' => 'AAAA-BBBBBBBB-CCCC']);
            ActivationCode::create(['code' => 'XXXX-YYYYYYYY-ZZZZ']);

            $this->actingAs(admin($this))
                ->get(route('auth.admin.activation-codes.index', ['code' => 'AAAA']))
                ->assertOk()
                ->assertSee('AAAA-BBBBBBBB-CCCC')
                ->assertDontSee('XXXX-YYYYYYYY-ZZZZ');
        });

        it('filters by status active', function () {
            ActivationCode::create(['code' => 'ACTI-VECODE1-2345']);
            ActivationCode::create(['code' => 'USED-CODEXXX-1111', 'used_at' => now(), 'used_by_user_id' => admin($this)->id]);

            $this->actingAs(admin($this))
                ->get(route('auth.admin.activation-codes.index', ['status' => 'active']))
                ->assertOk()
                ->assertSee('ACTI-VECODE1-2345')
                ->assertDontSee('USED-CODEXXX-1111');
        });
    });

    describe('create', function () {
        it('displays the create form for admins', function () {
            $this->actingAs(admin($this))
                ->get(route('auth.admin.activation-codes.create'))
                ->assertOk();
        });
    });

    describe('store', function () {
        it('generates a code with no optional fields', function () {
            $this->actingAs(admin($this))
                ->post(route('auth.admin.activation-codes.store'), [
                    'sponsor_user_id' => null,
                    'comment' => null,
                    'expires_at' => null,
                ])
                ->assertRedirect(route('auth.admin.activation-codes.index'));

            $this->assertDatabaseCount('user_activation_codes', 1);
        });

        it('generates a code with a comment', function () {
            $this->actingAs(admin($this))
                ->post(route('auth.admin.activation-codes.store'), [
                    'comment' => 'Test comment',
                ])
                ->assertRedirect(route('auth.admin.activation-codes.index'));

            $this->assertDatabaseHas('user_activation_codes', ['comment' => 'Test comment']);
        });

        it('generates a code with an expiry date', function () {
            $this->actingAs(admin($this))
                ->post(route('auth.admin.activation-codes.store'), [
                    'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
                ])
                ->assertRedirect(route('auth.admin.activation-codes.index'));

            $this->assertDatabaseCount('user_activation_codes', 1);
            $this->assertDatabaseMissing('user_activation_codes', ['expires_at' => null]);
        });

        it('rejects a past expiry date', function () {
            $this->actingAs(admin($this))
                ->post(route('auth.admin.activation-codes.store'), [
                    'expires_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ])
                ->assertSessionHasErrors(['expires_at']);
        });

        it('rejects an invalid sponsor_user_id', function () {
            $this->actingAs(admin($this))
                ->post(route('auth.admin.activation-codes.store'), [
                    'sponsor_user_id' => 99999,
                ])
                ->assertSessionHasErrors(['sponsor_user_id']);
        });
    });

    describe('destroy', function () {
        it('deletes an unused code', function () {
            $code = ActivationCode::create(['code' => 'DELE-TECODE-1234']);

            $this->actingAs(admin($this))
                ->delete(route('auth.admin.activation-codes.destroy', $code))
                ->assertRedirect(route('auth.admin.activation-codes.index'));

            $this->assertDatabaseMissing('user_activation_codes', ['id' => $code->id]);
        });

        it('refuses to delete a used code', function () {
            $user = admin($this);
            $code = ActivationCode::create([
                'code' => 'USED-CODEXXX-5678',
                'used_at' => now(),
                'used_by_user_id' => $user->id,
            ]);

            $this->actingAs($user)
                ->delete(route('auth.admin.activation-codes.destroy', $code))
                ->assertRedirect();

            $this->assertDatabaseHas('user_activation_codes', ['id' => $code->id]);
        });
    });
});
