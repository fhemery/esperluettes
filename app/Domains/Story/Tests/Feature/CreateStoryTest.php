<?php

use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('redirects guests from create page to login', function () {
    $response = $this->get('/stories/create');

    $response->assertRedirect('/login');
});

it('denies non-confirmed users from accessing the create page', function () {
    $user = alice($this, roles: ['user']);

    $resp = $this->actingAs($user)->get('/stories/create');

    // CheckRole middleware redirects unauthorized roles to dashboard
    $resp->assertRedirect(route('dashboard'));
});

it('denies non-confirmed users from posting new stories', function () {
    $user = alice($this, roles: ['user']);
    $this->actingAs($user);

    $payload = validStoryPayload([
        'title' => 'Blocked Title',
        'description' => '<p>blocked</p>',
    ]);

    $resp = $this->post('/stories', $payload);

    // Redirected to dashboard due to missing user-confirmed role
    $resp->assertRedirect(route('dashboard'));

    // Ensure nothing was created
    expect(Story::query()->count())->toBe(0);
});

it('produces unique slugs for duplicate titles', function () {
    // Arrange
    $user = alice($this, roles: ['user-confirmed']);
    $this->actingAs($user);

    $payload = validStoryPayload([
        'title' => 'Same Title',
        'description' => '<p>content</p>',
    ]);

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
        ->and($first->slug)->toEndWith('-' . $first->id)
        ->and($second->slug)->toStartWith($base . '-')
        ->and($second->slug)->toEndWith('-' . $second->id)
        ->and($first->slug)->not->toEqual($second->slug);

    // Show pages should be reachable via slug-with-id
    $this->get('/stories/' . $first->slug)->assertOk();
    $this->get('/stories/' . $second->slug)->assertOk();
});

it('allows an authenticated user to create a story and see it', function () {
    // Arrange
    $user = alice($this, roles: ['user-confirmed']);
    $this->actingAs($user);

    // Act
    $payload = validStoryPayload([
        'title' => 'My First Story',
        'description' => '<p>This is a great story</p>',
    ]);

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
    $show->assertSee('story::show.edit');
    // Type label and name displayed
    $show->assertSee(trans('story::shared.type.label'));
    $show->assertSee(defaultStoryType()->name);
    // Genres label and at least default genre displayed
    $show->assertSee(trans('story::shared.genres.label'));
    $show->assertSee(defaultGenre()->name);
});

it('displays multiple selected genres as badges on show page', function () {
    // Arrange
    $user = alice($this, roles: ['user-confirmed']);
    $this->actingAs($user);

    $g1 = makeGenre('Fantasy');
    $g2 = makeGenre('Romance');
    $payload = validStoryPayload([
        'title' => 'Genreful',
        'story_ref_genre_ids' => [$g1->id, $g2->id],
    ]);

    // Act
    $resp = $this->post('/stories', $payload);
    $resp->assertRedirect();

    $story = \App\Domains\Story\Models\Story::query()->firstOrFail();
    $show = $this->get('/stories/' . $story->slug);

    // Assert
    $show->assertOk();
    $show->assertSee(trans('story::shared.genres.label'));
    $show->assertSee('Fantasy');
    $show->assertSee('Romance');
});

it('allows creating a story with an optional status which is shown on the page', function () {
    // Arrange
    $user = alice($this, roles: ['user-confirmed']);
    $this->actingAs($user);
    $status = makeStatus('Ongoing');

    // Act
    $payload = validStoryPayload([
        'title' => 'Status Story',
        'description' => '<p>desc</p>',
        'story_ref_status_id' => $status->id,
    ]);
    $resp = $this->post('/stories', $payload);
    $resp->assertRedirect();

    // Assert
    $story = Story::query()->firstOrFail();
    expect($story->story_ref_status_id)->toBe($status->id);

    $show = $this->get('/stories/' . $story->slug);
    $show->assertOk();
    $show->assertSee(trans('story::shared.status.label'));
    $show->assertSee($status->name);
});
