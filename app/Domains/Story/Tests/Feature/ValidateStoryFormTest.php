<?php

use App\Domains\Auth\Models\User;
use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows errors for missing required fields (title, visibility); description optional', function () {
    $user = User::factory()->create();

    // Act: submit empty form
    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', []);

    // Assert redirect back
    $response->assertRedirect('/stories/create');

    // Follow redirect and assert error messages (raw i18n keys per .env.testing)
    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.title.required');
    $page->assertSee('story::validation.visibility.required');
    // Description is optional; should not show required error
    $page->assertDontSee('story::validation.description.required');
});

it('validates title too long (>255)', function () {
    $user = User::factory()->create();

    $payload = [
        'title' => str_repeat('a', 256),
        'description' => 'Ok',
        'visibility' => Story::VIS_PUBLIC,
    ];

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.title.max');
});

it('validates description max length (3000)', function () {
    $user = User::factory()->create();

    $payload = [
        'title' => 'Valid',
        'description' => str_repeat('a', 3001),
        'visibility' => Story::VIS_PUBLIC,
    ];

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.description.max');
});

it('validates visibility must be in allowed set', function () {
    $user = User::factory()->create();

    $payload = [
        'title' => 'Valid',
        'description' => null,
        'visibility' => 'friends', // invalid
    ];

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.visibility.in');
});
