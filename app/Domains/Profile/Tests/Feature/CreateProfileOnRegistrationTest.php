<?php

use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Support\Str;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a profile with display_name and slug on user registration', function () {
    // Act: call the registration HTTP endpoint which dispatches the events
    $user = registerUserThroughForm($this, [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    // Assert: profile exists with correct display_name and slug
    $profile = Profile::where('user_id', $user->id)->first();
    expect($profile)->not->toBeNull();
    expect($profile->display_name)->toBe('Jane Doe');
    expect($profile->slug)->toBe(Str::slug('Jane Doe'));
});
