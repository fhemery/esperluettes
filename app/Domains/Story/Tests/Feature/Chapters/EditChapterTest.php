<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Story\Models\Chapter;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('allows the author to access the chapter edit form', function () {
    $author = alice($this);
    $story = publicStory('Story A', $author->id);
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 1']);

    $this->actingAs($author);
    $resp = $this->get('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit');
    $resp->assertOk();
    // Ensure form fields are present
    $resp->assertSee('name="title"', false);
    $resp->assertSee('name="content"', false);
});

it('returns 404 for non-author accessing edit form', function () {
    $author = alice($this);
    $other = bob($this);
    $story = publicStory('Story B', $author->id);
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 2']);

    $this->actingAs($other);
    $this->get('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit')->assertNotFound();
});

it('updates chapter with sanitized content, regenerates slug base, keeps id suffix, and sets first_published_at on first publish', function () {
    $author = alice($this);
    $story = publicStory('Story C', $author->id);
    // Start as draft
    $chapter = createUnpublishedChapter($this, $story, $author, ['title' => 'Old Title']);
    $oldId = $chapter->id;

    $this->actingAs($author);
    $payload = [
        'title' => 'New Title',
        'author_note' => '<script>alert(1)</script><p>Note</p>',
        'content' => '<h1>  New <em>Content</em></h1>',
        'published' => '1',
    ];
    $resp = $this->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);
    $resp->assertRedirect();

    $chapterRefreshed = Chapter::query()->findOrFail($oldId);
    expect($chapterRefreshed->title)->toBe('New Title');
    // slug should end with -id suffix and base updated
    expect(str_ends_with($chapterRefreshed->slug, '-' . $oldId))->toBeTrue();
    expect(str_starts_with($chapterRefreshed->slug, 'new-title'))->toBeTrue();
    // status and first_published_at
    expect($chapterRefreshed->status)->toBe(Chapter::STATUS_PUBLISHED);
    expect($chapterRefreshed->first_published_at)->not->toBeNull();
    // content sanitized (no <script>)
    expect(str_contains($chapterRefreshed->content, '<script>'))->toBeFalse();
});

it('fails validation when content becomes empty after purification', function () {
    $author = alice($this);
    $story = publicStory('Story D', $author->id);
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 4']);

    $this->actingAs($author);
    // content with only empty block
    $payload = [
        'title' => 'Still Title',
        'author_note' => null,
        'content' => '   ',
    ];
    $resp = $this->from('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit')
        ->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);

    $resp->assertRedirect();
    $resp->assertSessionHasErrors(['content']);
});

it('fails validation when author note exceeds logical 1000 chars', function () {
    $author = alice($this);
    $story = publicStory('Story E', $author->id);
    $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Ch 5']);

    $this->actingAs($author);
    $long = str_repeat('a', 1001);
    $payload = [
        'title' => 'T',
        'author_note' => '<p>' . $long . '</p>',
        'content' => '<p>Ok</p>',
    ];
    $resp = $this->from('/stories/' . $story->slug . '/chapters/' . $chapter->slug . '/edit')
        ->put('/stories/' . $story->slug . '/chapters/' . $chapter->slug, $payload);

    $resp->assertRedirect();
    $resp->assertSessionHasErrors(['author_note']);
});
