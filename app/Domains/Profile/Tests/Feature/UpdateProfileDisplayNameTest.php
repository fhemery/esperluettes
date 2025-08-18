<?php

use App\Domains\Auth\Models\User;
use App\Domains\Profile\Events\ProfileDisplayNameChanged;
use App\Domains\Profile\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('updates display_name and slug when editing the display name', function () {
    // Arrange: register user through real endpoint and verify
    $user = registerUserThroughForm($this, [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Act: authenticated + verified user updates display name
    $this->actingAs($user)
        ->put('/profile', [
            'display_name' => 'Johnny Bravo',
        ])
        ->assertRedirect();

    // Assert: profile updated with new display name and new slug
    $updated = Profile::where('user_id', $user->id)->firstOrFail();
    expect($updated->display_name)->toBe('Johnny Bravo');
    expect($updated->slug)->toBe(Str::slug('Johnny Bravo'));
});

it('dispatches ProfileDisplayNameChanged event when updating the display name', function () {
    // Arrange: register user and verify
    $user = registerUserThroughForm($this, [
        'name' => 'John Doe',
        'email' => 'john2@example.com',
    ]);

    Event::fake();

    // Act
    $this->actingAs($user)
        ->put('/profile', [
            'display_name' => 'Johnny Bravo',
        ])
        ->assertRedirect();

    // Assert
    Event::assertDispatched(ProfileDisplayNameChanged::class, function (ProfileDisplayNameChanged $event) use ($user) {
        return $event->userId === $user->id
            && $event->oldDisplayName === 'John Doe'
            && $event->newDisplayName === 'Johnny Bravo';
    });
});
