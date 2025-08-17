<?php

use App\Domains\Story\Models\Story;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from create page to login', function () {
    $response = $this->get('/stories/create');

    $response->assertRedirect('/login');
});

it('allows an authenticated user to create a story and see it', function () {
    // Arrange
    $user = User::factory()->create();
    $this->actingAs($user);

    // Force a non-existent locale so translation falls back to the key itself
    app()->setLocale('xx');

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
    // The show view uses __('story::create.form.visibility.options.public')
    // With a non-existent locale, __() returns the key itself
    $show->assertSee('story::create.form.visibility.options.public');
    // Edit action visible for author; with non-existent locale, assert the key itself
    $show->assertSee('story::show.edit');
});
