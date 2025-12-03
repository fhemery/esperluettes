<?php

use App\Domains\Auth\Private\Models\ActivationCode;
use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

describe('Compliance at login', function () {
    describe('About terms and conditions acceptance', function () {

        test('user without accepted terms is redirected to terms page', function () {
            $user = alice($this);
            $user->update(['terms_accepted_at'=> null]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertRedirect(route('compliance.terms.show'));
        });

        test('user can accept terms', function () {
            $user = alice($this);
            $user->update(['terms_accepted_at'=> null]);

            $this->actingAs($user)
                ->post(route('compliance.terms.accept'), [
                    'accept_terms' => '1',
                ])
                ->assertRedirect(route('dashboard'));

            expect($user->fresh()->terms_accepted_at)->not->toBeNull();
        });

        test('terms acceptance requires checkbox', function () {
            $user = alice($this);
            $user->update(['terms_accepted_at'=> null]);

            $this->actingAs($user)
                ->post(route('compliance.terms.accept'), [
                    'accept_terms' => '0',
                ])
                ->assertSessionHasErrors('accept_terms');

            expect($user->fresh()->terms_accepted_at)->toBeNull();
        });
    });

    describe('Underage user access', function () {

        test('underage user without parental auth is redirected after terms', function () {
            $user = alice($this, [
                'is_under_15' => true,
            ]);

            $this->actingAs($user)
                ->post(route('compliance.terms.accept'), [
                    'accept_terms' => '1',
                ])
                ->assertRedirect(route('compliance.parental.show'));
        });

        test('underage user without terms cannot access parental page', function () {
            $user = alice($this, [
                'is_under_15' => true,
            ]);
            $user->update(['terms_accepted_at' => null]);

            $this->actingAs($user)
                ->get(route('compliance.parental.show'))
                ->assertViewIs('auth::pages.compliance.terms');
        });

        test('underage user can upload parental authorization', function () {
            $user = alice($this, [
                'is_under_15' => true,
            ]);

            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024);

            $this->actingAs($user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ])
                ->assertRedirect(route('dashboard'));

            expect($user->fresh()->parental_authorization_verified_at)->not->toBeNull();
            $fileName = 'authorization-' . $user->id . '.pdf';
            Storage::disk('private')->assertExists('parental_authorizations/' . $fileName);
        });

        test('parental authorization upload validates file type', function () {
            $user = alice($this, [
                'is_under_15' => true,
            ]);

            $file = UploadedFile::fake()->create('invalid.txt', 100);

            $this->actingAs($user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ])
                ->assertSessionHasErrors('parental_authorization');

            expect($user->fresh()->parental_authorization_verified_at)->toBeNull();
        });

        test('compliant user can access dashboard', function () {
            $user = alice($this);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk();
        });

        test('compliant underage user with auth can access dashboard', function () {
            $user = alice($this, [
                'is_under_15' => true,
            ]);
            
            $file = UploadedFile::fake()->create('invalid.pdf', 100);

            $this->actingAs($user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ])->assertRedirect(route('dashboard'));

        });

        test('compliance check is cached in session', function () {
            $user = alice($this);

            // First request should check and cache
            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk();

            expect(session()->has('user_compliance_checked_' . $user->id))->toBeTrue();

            // Second request should use cached result
            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk();
        });

        test('guest user is not checked for compliance', function () {
            Auth::logout();
            $this->get(route('dashboard'))
                ->assertRedirect(route('login'));
        });

        test('logout route bypasses compliance check', function () {
            $user = alice($this);
            $user->update(['terms_accepted_at' => null]);

            $this->actingAs($user)
                ->post(route('logout'))
                ->assertRedirect('/');

            $this->assertGuest();
        });
    });

    describe('Role assignment for underage users', function () {

        test('underage user with verified email gets roles upon parental auth upload', function () {
            // Activation code is optional
            setActivationCodeRequired(false);

            // Create an activation code for a sponsored user
            $code = ActivationCode::create([
                'code' => 'TEST-CODE-123',
                'sponsor_user_id' => null,
                'expires_at' => now()->addDays(7),
            ]);

            // Create verified under-15 user without roles (sponsored with activation code)
            $user = registerUserThroughForm($this, [
                'email' => 'minor-verified@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_under_15' => true,
                'activation_code' => 'TEST-CODE-123',
            ], true, []); // verified, no roles

            // Sanity check: no roles yet (underage user waits for parental auth)
            expect($user->isConfirmed())->toBeFalse();
            expect($user->isOnProbation())->toBeFalse();

            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024);

            $this->actingAs($user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ])
                ->assertRedirect(route('dashboard'));

            // Should now have confirmed role (sponsored user)
            $user->refresh();
            expect($user->parental_authorization_verified_at)->not->toBeNull();
            expect($user->isConfirmed())->toBeTrue();
        });

        test('underage user without verified email does not get roles upon parental auth upload', function () {
            // Activation code is optional
            setActivationCodeRequired(false);

            // Create unverified under-15 user without roles
            $user = registerUserThroughForm($this, [
                'email' => 'minor-unverified@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_under_15' => true,
            ], false, []); // not verified, no roles

            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024);

            $this->actingAs($user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ])
                ->assertRedirect(route('dashboard'));

            // Should NOT have roles (waiting for email verification)
            $user->refresh();
            expect($user->parental_authorization_verified_at)->not->toBeNull();
            expect($user->isConfirmed())->toBeFalse();
            expect($user->isOnProbation())->toBeFalse();
            expect($user->roles)->toBeEmpty();
        });

        test('unsponsored underage user gets USER role upon parental auth upload (no activation code)', function () {
            // Activation code is optional
            setActivationCodeRequired(false);

            // Create verified under-15 user WITHOUT activation code (unsponsored)
            $user = registerUserThroughForm($this, [
                'email' => 'minor-unsponsored@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_under_15' => true,
                // NO activation_code provided
            ], true, []); // verified, no roles

            // Sanity check: no roles yet (underage user waits for parental auth)
            expect($user->isConfirmed())->toBeFalse();
            expect($user->isOnProbation())->toBeFalse();

            $file = UploadedFile::fake()->create('parental_auth.pdf', 1024);

            $this->actingAs($user)
                ->post(route('compliance.parental.upload'), [
                    'parental_authorization' => $file,
                ])
                ->assertRedirect(route('dashboard'));

            // Should have USER role (probation), not USER_CONFIRMED
            $user->refresh();
            expect($user->parental_authorization_verified_at)->not->toBeNull();
            expect($user->isOnProbation())->toBeTrue();
            expect($user->isConfirmed())->toBeFalse();
        });

    });
});
