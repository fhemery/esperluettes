<?php

use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Auth\Events\UserRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Register', function () {

    describe('Data validation', function () {
        it('allows registration when name is unique', function () {
            $response = $this->post('/register', [
                'name' => 'Unique Person',
                'email' => 'unique@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
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
            ]);

            $second->assertRedirect('/register');
            $second->assertSessionHasErrors(['name']);

            // Ensure user not created for the second attempt
            $this->assertDatabaseMissing('users', ['email' => 'alice2@example.com']);
        });
    });

    describe('Events', function () {
        it('emits and persists Auth.UserRegistered when registration succeeds', function () {
            $this->post('/register', [
                'name' => 'Event Tester',
                'email' => 'eventtester@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertRedirect();

            /* @var UserRegistered $dto */
            $dto = latestEventOf(UserRegistered::name(), UserRegistered::class);
            expect($dto)->not->toBeNull();
            expect($dto->userId)->toBe(Auth::id());
            expect($dto->displayName)->toBe('Event Tester');
        });
    });
});
