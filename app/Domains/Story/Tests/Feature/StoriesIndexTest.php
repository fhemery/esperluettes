<?php

use App\Domains\Auth\Models\User;
use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('shows empty state when there are no public stories', function () {
    // Act
    $response = $this->get('/stories');

    // Assert
    $response->assertOk();
    $response->assertSee('story::index.empty');
});

it('should only list public stories to unlogged users, title and author', function () {
    // Arrange
    $author = alice($this);

    // Public story (should appear)
    $public = publicStory('Public Story', $author->id, [
        'description' => '<p>Desc</p>',
    ]);

    // Private story (should not appear)
    privateStory('Private Story', $author->id, [
        'description' => '<p>Hidden</p>',
    ]);

    // Community story (should not appear)
    communityStory('Community Story', $author->id, [
        'description' => '<p>Hidden</p>',
    ]);

    // Act
    $resp = $this->get('/stories');

    // Assert
    $resp->assertOk();
    $resp->assertSee($public->title);
    $resp->assertSee('story::index.by_author');
    $resp->assertDontSee('Private Story');
    $resp->assertDontSee('Community Story');
});

it('should show public and community stories to logged users', function () {
    // Arrange
    $author = alice($this);

    // Public story (should appear)
    $public = publicStory('Public Story', $author->id, [
        'description' => '<p>Desc</p>',
    ]);

    // Private story (should not appear)
    privateStory('Private Story', $author->id, [
        'description' => '<p>Hidden</p>',
    ]);

    // Community story (should not appear)
    communityStory('Community Story', $author->id, [
        'description' => '<p>Hidden</p>',
    ]);

    // Act
    $resp = $this->actingAs(bob($this))->get('/stories');

    // Assert
    $resp->assertOk();
    $resp->assertSee($public->title);
    $resp->assertSee('story::index.by_author');
    $resp->assertDontSee('Private Story');
    $resp->assertDontSee('Community Story');
});


it('paginates 24 stories ordered by creation date desc', function () {
    // Arrange
    $author = alice($this);

    // Create 30 public stories and then set created_at explicitly for ordering
    for ($i = 1; $i <= 30; $i++) {
        $story = publicStory(sprintf('Story %02d', $i), $author->id, [
            'description' => '<p>Desc</p>',
        ]);
        $story->created_at = now()->subMinutes(60 - $i);
        $story->updated_at = $story->created_at;
        $story->saveQuietly();
    }

    // Act: first page
    $page1 = $this->get('/stories');

    // Assert: shows newest first (Story 30) and does not include oldest beyond 24 (Story 06)
    $page1->assertOk();
    $page1->assertSee('Story 30');
    $page1->assertDontSee('Story 06');
    $page1->assertSee('/stories?page=2'); // pagination link to next page

    // Act: second page
    $page2 = $this->get('/stories?page=2');

    // Assert: includes older ones
    $page2->assertOk();
    $page2->assertSee('Story 06');
    $page2->assertSee('Story 01');
});
