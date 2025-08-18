<?php

use App\Domains\Profile\Models\Profile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a profile with display_name and slug on user registration', function () {
    // Act: call the registration HTTP endpoint which dispatches the events
    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);
    $response->assertRedirect();

    // Fetch the created user id
    $userId = DB::table('users')->where('email', 'jane@example.com')->value('id');

    // Assert: profile exists with correct display_name and slug
    $profile = Profile::where('user_id', $userId)->first();
    expect($profile)->not->toBeNull();
    expect($profile->display_name)->toBe('Jane Doe');
    expect($profile->slug)->toBe(Str::slug('Jane Doe'));
});
