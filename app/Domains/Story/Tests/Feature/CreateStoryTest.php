<?php

use App\Domains\Story\Models\Story;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guests from create page to login', function () {
    $response = $this->get('/stories/create');

    $response->assertRedirect('/login');
});

it('produces unique slugs for duplicate titles', function () {
    // Arrange
    $user = User::factory()->create();
    $this->actingAs($user);

    $payload = [
        'title' => 'Same Title',
        'description' => '<p>content</p>',
        'visibility' => Story::VIS_PUBLIC,
    ];

    // Act: create two stories with identical titles
    $this->post('/stories', $payload)->assertRedirect();
    $this->post('/stories', $payload)->assertRedirect();

    // Assert: both exist and have unique slug-with-id
    $stories = Story::query()->orderBy('id')->get();
    expect($stories)->toHaveCount(2);

    $first = $stories[0];
    $second = $stories[1];

    // Both slugs start with the same base, and end with their respective ids
    $base = \App\Domains\Story\Models\Story::generateSlugBase('Same Title');
    expect($first->slug)->toStartWith($base . '-')
        ->and($first->slug)->toEndWith('-' . $first->id);
    expect($second->slug)->toStartWith($base . '-')
        ->and($second->slug)->toEndWith('-' . $second->id);

    // Ensure they are different
    expect($first->slug)->not->toEqual($second->slug);

    // Show pages should be reachable via slug-with-id
    $this->get('/stories/' . $first->slug)->assertOk();
    $this->get('/stories/' . $second->slug)->assertOk();
});

it('allows an authenticated user to create a story and see it', function () {
    // Arrange
    $user = User::factory()->create();
    $this->actingAs($user);

    // Act
    $payload = [
        'title' => 'My First Story',
        'description' => '<p>This is a great story</p>',
        'visibility' => Story::VIS_PUBLIC,
    ];

    $response = $this->post('/stories', $payload);

    // Assert redirect to story page
    $response->assertRedirect();

    // Load created story
    $story = Story::query()->firstOrFail();

    // URL pattern contains slug-with-id
    expect($story->slug)->toEndWith('-' . $story->id);

    // Visit show page and assert content
    $show = $this->get('/stories/' . $story->slug);
    $show->assertOk();
    $show->assertSee('My First Story');
    $show->assertSee('story::shared.visibility.options.public');
    // Edit action visible for author; with non-existent locale, assert the key itself
    $show->assertSee('story::show.edit');
});
