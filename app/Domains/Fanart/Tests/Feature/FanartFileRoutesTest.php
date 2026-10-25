<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Support\FanartStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

it('streams a published image and its 400w thumbnail to a confirmed reader', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $author->id]);

    $this->actingAs(bob($this));

    $this->get(route('fanart.file', $fanart->id))->assertOk();
    $this->get(route('fanart.thumb', [$fanart->id, 400]))->assertOk();
    $this->get(route('fanart.thumb', [$fanart->id, 800]))->assertOk();
});

it('answers a Range request on an audio with 206', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createPendingAudio([
        'story_id' => $story->id,
        'user_id' => $author->id,
        'status' => FanartStatus::Published,
        'chapter_id' => createPublishedChapter($this, $story, $author)->id,
    ]);
    file_put_contents(Storage::disk('private')->path($fanart->file_path), random_bytes(2048));

    $this->actingAs(bob($this));
    $response = $this->withHeaders(['Range' => 'bytes=0-9'])->get(route('fanart.file', $fanart->id));

    $response->assertStatus(206);
    $response->assertHeader('Content-Range', 'bytes 0-9/2048');
});

it('sets Cache-Control private', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $author->id]);

    $this->actingAs(bob($this));

    expect($this->get(route('fanart.file', $fanart->id))->headers->get('Cache-Control'))
        ->toContain('private')
        ->and($this->get(route('fanart.thumb', [$fanart->id, 400]))->headers->get('Cache-Control'))
        ->toContain('private');
});

function publishedAudioForProtection($test)
{
    $author = alice($test);
    $story = publicStory('Histoire', $author->id);
    $fanart = createPendingAudio([
        'story_id' => $story->id,
        'user_id' => $author->id,
        'status' => FanartStatus::Published,
        'chapter_id' => createPublishedChapter($test, $story, $author)->id,
    ]);
    file_put_contents(Storage::disk('private')->path($fanart->file_path), random_bytes(2048));

    return [$author, $fanart];
}

it('refuses a sound opened by navigation, even for the artist', function () {
    [$author, $fanart] = publishedAudioForProtection($this);

    $this->actingAs(bob($this))->withHeaders(['Sec-Fetch-Dest' => 'document'])
        ->get(route('fanart.file', $fanart->id))->assertForbidden();
    $this->actingAs($author)->withHeaders(['Sec-Fetch-Dest' => 'document'])
        ->get(route('fanart.file', $fanart->id))->assertForbidden();
});

it('streams a sound to an audio element with no-store, and without the header', function () {
    [, $fanart] = publishedAudioForProtection($this);
    $this->actingAs(bob($this));

    $response = $this->withHeaders(['Sec-Fetch-Dest' => 'audio'])->get(route('fanart.file', $fanart->id));
    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->get(route('fanart.file', $fanart->id))->assertOk();
    $this->withHeaders(['Sec-Fetch-Dest' => 'audio', 'Range' => 'bytes=0-9'])
        ->get(route('fanart.file', $fanart->id))->assertStatus(206);
});

it('does not check Sec-Fetch-Dest on images', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $author->id]);

    $this->actingAs(bob($this))->withHeaders(['Sec-Fetch-Dest' => 'document'])
        ->get(route('fanart.file', $fanart->id))->assertOk();
});

it('returns 403 to a non-confirmed user and redirects a guest to login', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $author->id]);

    $this->get(route('fanart.file', $fanart->id))->assertRedirect(route('login'));

    $this->actingAs(bob($this, roles: [Roles::USER]));
    // The convention `role` middleware turns a non-confirmed user away with a redirect, never a body.
    $this->get(route('fanart.file', $fanart->id))->assertRedirect();
    $this->get(route('fanart.thumb', [$fanart->id, 400]))->assertRedirect();
});

it('returns 403 to a reader for a pending fanart, 200 to its submitter, an author and a moderator', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $submitter = carol($this);
    $fanart = createPublishedImage([
        'story_id' => $story->id,
        'user_id' => $submitter->id,
        'status' => FanartStatus::Pending,
        'published_at' => null,
    ]);

    $this->actingAs(bob($this));
    $this->get(route('fanart.file', $fanart->id))->assertForbidden();
    $this->get(route('fanart.thumb', [$fanart->id, 400]))->assertForbidden();

    foreach ([$submitter, $author, moderator($this)] as $user) {
        $this->actingAs($user);
        $this->get(route('fanart.file', $fanart->id))->assertOk();
        $this->get(route('fanart.thumb', [$fanart->id, 400]))->assertOk();
    }
});

it('returns 403 to a reader for a fanart on an unpublished chapter', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $draft = createUnpublishedChapter($this, $story, $author);
    $fanart = createPublishedImage(['story_id' => $story->id, 'chapter_id' => $draft->id, 'user_id' => $author->id]);

    $this->actingAs(bob($this));

    $this->get(route('fanart.file', $fanart->id))->assertForbidden();
    $this->get(route('fanart.thumb', [$fanart->id, 400]))->assertForbidden();
});

it('returns 404 for a soft-deleted fanart and for an unknown width', function () {
    $author = alice($this);
    $story = publicStory('Histoire', $author->id);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $author->id]);
    $audio = createPendingAudio(['story_id' => $story->id, 'user_id' => $author->id, 'chapter_id' => null, 'status' => FanartStatus::Published]);

    $this->actingAs(bob($this));

    $this->get(route('fanart.thumb', [$fanart->id, 123]))->assertNotFound();
    $this->get(route('fanart.thumb', [$audio->id, 400]))->assertNotFound();

    $fanart->delete();
    $this->get(route('fanart.file', $fanart->id))->assertNotFound();
    $this->get(route('fanart.thumb', [$fanart->id, 400]))->assertNotFound();
});
