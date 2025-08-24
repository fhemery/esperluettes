<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows errors for missing required fields (title, visibility, type, audience, copyright); description optional', function () {
    $user = alice($this);

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
    $page->assertSee('story::validation.type.required');
    $page->assertSee('story::validation.audience.required');
    $page->assertSee('story::validation.copyright.required');
    // Description is optional; should not show required error
    $page->assertDontSee('story::validation.description.required');
});

it('validates title too long (>255)', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'title' => str_repeat('a', 256),
        'description' => 'Ok',
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.title.max');
});

it('validates description max length (3000)', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'description' => str_repeat('a', 3001),
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.description.max');
});

it('validates visibility must be in allowed set', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'visibility' => 'friends', // invalid
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.visibility.in');
});

it('validates story_ref_type_id must be integer', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'story_ref_type_id' => 'abc',
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.type.integer');
});

it('validates story_ref_type_id must exist', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'story_ref_type_id' => 999999, // not existing
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.type.exists');
});

it('validates story_ref_audience_id must be integer', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'story_ref_audience_id' => 'abc',
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.audience.integer');
});

it('validates story_ref_audience_id must exist', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'story_ref_audience_id' => 999999,
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.audience.exists');
});

it('validates story_ref_copyright_id must be integer', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'story_ref_copyright_id' => 'abc',
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.copyright.integer');
});

it('validates story_ref_copyright_id must exist', function () {
    $user = alice($this);

    $payload = validStoryPayload([
        'story_ref_copyright_id' => 999999,
    ]);

    $response = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payload);

    $response->assertRedirect('/stories/create');

    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.copyright.exists');
});

it('validates genres are required and must be an array of 1 to 3', function () {
    $user = alice($this);

    // Missing genres
    $payloadMissing = validStoryPayload([
        'story_ref_genre_ids' => null,
    ]);
    $resp = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payloadMissing);
    $resp->assertRedirect('/stories/create');
    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.genres.required');

    // Not an array
    $payloadNotArray = validStoryPayload([
        'story_ref_genre_ids' => 1,
    ]);
    $resp = $this->actingAs($user)
        ->from('/stories/create')
        ->post('/stories', $payloadNotArray);
    $resp->assertRedirect('/stories/create');
    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.genres.array');
});

it('validates genres max 3', function () {
    $user = alice($this);
    // Max 3
    $g1 = makeGenre('G1');
    $g2 = makeGenre('G2');
    $g3 = makeGenre('G3');
    $g4 = makeGenre('G4');
    $payloadMax = validStoryPayload([
        'story_ref_genre_ids' => [$g1->id, $g2->id, $g3->id, $g4->id],
    ]);
    $resp = $this->actingAs($user)->from('/stories/create')->post('/stories', $payloadMax);
    $resp->assertRedirect('/stories/create');
    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.genres.max');
});

it('validates each genre id must be integer and exist', function () {
    $user = alice($this);

    // Non-integer
    $payloadType = validStoryPayload([
        'story_ref_genre_ids' => ['x'],
    ]);
    $resp = $this->actingAs($user)->from('/stories/create')->post('/stories', $payloadType);
    $resp->assertRedirect('/stories/create');
    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.genres.integer');

    // Non-existent id
    $payloadExists = validStoryPayload([
        'story_ref_genre_ids' => [999999],
    ]);
    $resp = $this->actingAs($user)->from('/stories/create')->post('/stories', $payloadExists);
    $resp->assertRedirect('/stories/create');
    $page = $this->followingRedirects()->actingAs($user)->get('/stories/create');
    $page->assertOk();
    $page->assertSee('story::validation.genres.exists');
});
