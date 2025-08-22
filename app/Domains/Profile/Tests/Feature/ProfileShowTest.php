<?php

declare(strict_types=1);

use App\Domains\Profile\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guests from own profile route to login', function () {
    $this->get('/profile')->assertRedirect('/login');
});

it('allows authenticated user with proper role to access own profile', function () {
    $userConfirmed = alice($this);
    $simpleUser = bob($this, roles: ['user']);

    $this->actingAs($simpleUser)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Bob');
    $this->actingAs($userConfirmed)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Alice');
});

it('allows guests to view a public profile page by slug and does not show about tab', function () {
    // Arrange: create a user + profile via helper
    $user = alice($this);
    $profile = Profile::where('user_id', $user->id)->firstOrFail();

    // Act: guest visits profile show page
    $response = $this->get("/profile/{$profile->slug}");

    // Assert: page is accessible
    $response->assertOk();

    // Guests should NOT see About tab label
    $response->assertDontSee('profile::show.about');

    // Guests should see Stories tab label
    $response->assertSee('profile::show.stories');
});

it('shows About tab to any authenticated user when viewing someone else\'s profile', function () {
    // Arrange: two users with profiles
    $alice = alice($this);
    $bob = registerUserThroughForm($this, [
        'name' => 'Bob Tester',
        'email' => 'bob@example.com',
    ]);

    $aliceProfile = Profile::where('user_id', $alice->id)->firstOrFail();

    // Act: Bob (authenticated) views Alice's profile
    $response = $this->actingAs($bob)->get("/profile/{$aliceProfile->slug}");

    // Assert: About tab is visible to authenticated users
    $response->assertOk();
    $response->assertSee('profile::show.about');
    $response->assertSee('profile::show.stories');
});


it('shows the authenticated validated user name on profile page', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response->assertOk();
    $response->assertSee('Alice');
});

it('should show profile edit button if user is current user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response->assertOk();
    $response->assertSee('Alice');
    $response->assertSee('profile::show.edit_profile');
});

it('should show "My stories" tab instead of "Stories" if user is current user', function () {
    $user = alice($this);

    $this->actingAs($user)->get('/profile')
        ->assertSee('profile::show.my-stories');
});
