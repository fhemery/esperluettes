<?php

use App\Domains\Auth\Private\Models\User;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Auth\Public\Events\UserRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Register', function () {
    beforeEach(function() {
        setActivationCodeRequired(false);
    });

    describe('Data validation', function () {
        it('allows registration when name is unique', function () {
            
            $response = $this->post('/register', [
                'name' => 'Unique Person',
                'email' => 'unique@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            $response->assertRedirect();

            // profile created with display_name
            $this->assertDatabaseHas('users', ['email' => 'unique@example.com']);
            $this->assertDatabaseHas('profile_profiles', ['display_name' => 'Unique Person']);
        });

        it('rejects registration when the normalized slug would collide with an existing profile', function () {
            // First registration => creates profile with some normalized slug
            $first = $this->post('/register', [
                'name' => 'Me!',
                'email' => 'me1@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);
            $first->assertRedirect();

            // Ensure we are guest for the second attempt
            $this->post('/logout');

            // Second registration with a different display string that normalizes to the same slug
            $second = $this->from('/register')->post('/register', [
                'name' => 'me-',
                'email' => 'me2@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            // Expect validation error on name and redirect back to /register
            $second->assertRedirect('/register');
            $second->assertSessionHasErrors(['name']);

            // No user should be created for the second attempt
            $this->assertDatabaseMissing('users', ['email' => 'me2@example.com']);
        });

        it('rejects registration when a profile already uses the same display name', function () {
            // First registration creates a profile with display_name "Alice"
            $first = $this->post('/register', [
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);
            $first->assertRedirect();

            // Ensure we are guest for the second attempt
            $this->post('/logout');

            // Sanity check
            expect(Profile::where('display_name', 'Alice')->exists())->toBeTrue();

            // Second registration tries to reuse the same name => should fail validation on name
            $second = $this->from('/register')->post('/register', [
                'name' => 'Alice',
                'email' => 'alice2@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            $second->assertRedirect('/register');
            $second->assertSessionHasErrors(['name']);

            // Ensure user not created for the second attempt
            $this->assertDatabaseMissing('users', ['email' => 'alice2@example.com']);
        });

        it('rejects registration when name is empty', function () {
            $response = $this->from('/register')->post('/register', [
                'name' => '',
                'email' => 'empty@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            $response->assertRedirect('/register');
            $response->assertSessionHasErrors(['name']);
            $this->assertDatabaseMissing('users', ['email' => 'empty@example.com']);
        });

        it('rejects registration when name is only 1 character', function () {
            $response = $this->from('/register')->post('/register', [
                'name' => 'A',
                'email' => 'single@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            $response->assertRedirect('/register');
            $response->assertSessionHasErrors(['name']);
            $this->assertDatabaseMissing('users', ['email' => 'single@example.com']);
        });

        it('allows registration when name is exactly 2 characters', function () {
            $response = $this->post('/register', [
                'name' => 'AB',
                'email' => 'two@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('users', ['email' => 'two@example.com']);
        });

        it('rejects registration when activation code is mandatory', function () {
            setActivationCodeRequired(true);
            $response = $this->from('/register')->post('/register', [
                'name' => 'John',
                'email' => 'empty@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ]);

            $response->assertRedirect('/register');
            $response->assertSessionHasErrors(['activation_code']);
            $this->assertDatabaseMissing('users', ['email' => 'empty@example.com']);
        });

        describe('Compliance validation', function () {
            it('rejects registration when terms are not accepted', function () {
                $response = $this->from('/register')->post('/register', [
                    'name' => 'Test User',
                    'email' => 'noterms@example.com',
                    'password' => 'secret-password',
                    'password_confirmation' => 'secret-password',
                    'is_under_15' => false,
                    // accept_terms is missing
                ]);

                $response->assertRedirect('/register');
                $response->assertSessionHasErrors(['accept_terms']);
                $this->assertDatabaseMissing('users', ['email' => 'noterms@example.com']);
            });

            it('allows registration when underage checkbox is checked', function () {
                $response = $this->post('/register', [
                    'name' => 'Underage User',
                    'email' => 'underage@example.com',
                    'password' => 'secret-password',
                    'password_confirmation' => 'secret-password',
                    'is_under_15' => true,
                    'accept_terms' => '1',
                ]);

                $response->assertRedirect();
                $this->assertDatabaseHas('users', [
                    'email' => 'underage@example.com',
                    'is_under_15' => true,
                ]);
                
                // Verify terms were accepted
                $user = User::where('email', 'underage@example.com')->first();
                expect($user->terms_accepted_at)->not->toBeNull();
            });

            it('redirects underage user to parental authorization after registration', function () {
                $response = $this->post('/register', [
                    'name' => 'Underage User',
                    'email' => 'underage2@example.com',
                    'password' => 'secret-password',
                    'password_confirmation' => 'secret-password',
                    'is_under_15' => true,
                    'accept_terms' => '1',
                ]);

                // Should redirect to parental authorization due to middleware
                $response->assertRedirect(route('compliance.parental.show'));
                
                // Verify user was created with correct compliance data
                $this->assertDatabaseHas('users', [
                    'email' => 'underage2@example.com',
                    'is_under_15' => true,
                    'parental_authorization_verified_at' => null,
                ]);
                
                // Verify terms were accepted
                $user = User::where('email', 'underage2@example.com')->first();
                expect($user->terms_accepted_at)->not->toBeNull();
            });

            it('allows adult user to access dashboard after registration', function () {
                $response = $this->post('/register', [
                    'name' => 'Adult User',
                    'email' => 'adult@example.com',
                    'password' => 'secret-password',
                    'password_confirmation' => 'secret-password',
                    'is_under_15' => false,
                    'accept_terms' => '1',
                ]);

                // Should redirect to dashboard (user is compliant)
                $response->assertRedirect(route('dashboard'));
                
                // Verify user was created with correct compliance data
                $this->assertDatabaseHas('users', [
                    'email' => 'adult@example.com',
                    'is_under_15' => false,
                ]);
                
                // Verify terms were accepted
                $user = \App\Domains\Auth\Private\Models\User::where('email', 'adult@example.com')->first();
                expect($user->terms_accepted_at)->not->toBeNull();
            });
        });
    });

    describe('Events', function () {
        it('emits and persists Auth.UserRegistered when registration succeeds', function () {
            $this->post('/register', [
                'name' => 'Event Tester',
                'email' => 'eventtester@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_under_15' => false,
                'accept_terms' => '1',
            ])->assertRedirect();

            /* @var UserRegistered $dto */
            $dto = latestEventOf(UserRegistered::name(), UserRegistered::class);
            expect($dto)->not->toBeNull();
            expect($dto->userId)->toBe(Auth::id());
            expect($dto->displayName)->toBe('Event Tester');
        });
    });
});
