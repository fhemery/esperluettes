<?php

declare(strict_types=1);

use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);


it('redirects guests from profile edit route to login', function () {
    $this->get('/profile/edit')->assertRedirect('/login');
});


it('allows authenticated user with proper role to access edit page', function () {
    $userConfirmed = alice($this);
    $simpleUser = bob($this, roles: [Roles::USER]);

    $this->actingAs($simpleUser)
        ->get('/profile/edit')
        ->assertOk()
        ->assertSee('Bob');
    $this->actingAs($userConfirmed)
        ->get('/profile/edit')
        ->assertOk()
        ->assertSee('Alice');
});

describe('Name update', function () {
    it('rejects duplicate display name on profile update', function () {
        // Register two users via real flow
        $userA = registerUserThroughForm($this, [
            'name' => 'Alice Unique',
            'email' => 'alice.unique@example.com',
        ]);
        $userB = registerUserThroughForm($this, [
            'name' => 'Bob Starter',
            'email' => 'bob.starter@example.com',
        ]);

        // Bob tries to change display name to Alice's
        $response = $this->actingAs($userB)
            ->from('/profile/edit')
            ->put('/profile', [
                'display_name' => 'Alice Unique',
            ]);

        $response->assertRedirect('/profile/edit');
        $response->assertSessionHasErrors(['display_name']);

        // Act: Bob attempts to change display_name to a value that normalizes to same slug
        $response = $this->actingAs($userB)
            ->from('/profile/edit')
            ->put('/profile', [
                'display_name' => 'alice !unIquE',
            ]);

        // Assert: validation error and redirect back
        $response->assertRedirect('/profile/edit');
        $response->assertSessionHasErrors(['display_name']);
    });

    it('allows keeping the same display name (self-update)', function () {
        // Arrange: register a user and capture current display_name
        $user = registerUserThroughForm($this, [
            'name' => 'Same Name',
            'email' => 'same.name@example.com',
        ]);

        // Act: user submits the same display_name
        $response = $this->actingAs($user)
            ->from('/profile')
            ->put('/profile', [
                'display_name' => 'Same Name',
            ]);

        // Assert: success redirect, no validation errors, name unchanged
        $response->assertRedirect('/profile/edit');
        $response->assertSessionHasNoErrors();
    });
});

describe('SEO', function () {
    it('should have the correct title', function () {
        $user = alice($this);

        $this->actingAs($user)->get('/profile/edit')
            ->assertSee(__('profile::edit.title', ['name' => 'Alice']));
    });
});
