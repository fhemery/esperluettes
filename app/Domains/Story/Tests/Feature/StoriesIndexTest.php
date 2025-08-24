<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('displays genre badges on list cards', function () {
    // Arrange
    $author = alice($this);
    $fantasy = makeGenre('Fantasy');
    $mystery = makeGenre('Mystery');

    publicStory('Genreful Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_genre_ids' => [$fantasy->id, $mystery->id],
    ]);

    // Act
    $resp = $this->get('/stories');

    // Assert: badges show both genre names
    $resp->assertOk();
    $resp->assertSee('Genreful Story');
    $resp->assertSee('Fantasy');
    $resp->assertSee('Mystery');
});

it('filters stories by a single genre slug', function () {
    // Arrange
    $author = alice($this);
    $romance = makeGenre('Romance');
    $sciFi = makeGenre('Sci Fi');

    publicStory('Romance Tale', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_genre_ids' => [$romance->id],
    ]);

    publicStory('Science Fiction', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_genre_ids' => [$sciFi->id],
    ]);

    // Act
    $resp = $this->get('/stories?genres=' . $romance->slug);

    // Assert: only romance story visible
    $resp->assertOk();
    $resp->assertSee('Romance Tale');
    $resp->assertDontSee('Science Fiction');
    $resp->assertSee(trans('story::index.filter'));
    $resp->assertSee(trans('story::index.reset_filters'));
});

it('filters stories by multiple genre slugs (AND semantics)', function () {
    // Arrange
    $author = alice($this);
    $horror = makeGenre('Horror');
    $comedy = makeGenre('Comedy');
    $drama = makeGenre('Drama');

    // Matches both Horror AND Comedy
    publicStory('Horror Comedy', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_genre_ids' => [$horror->id, $comedy->id],
    ]);

    // Only Horror
    publicStory('Just Horror', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_genre_ids' => [$horror->id],
    ]);

    // Only Comedy
    publicStory('Just Comedy', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_genre_ids' => [$comedy->id],
    ]);

    // Unrelated
    publicStory('Only Drama', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_genre_ids' => [$drama->id],
    ]);

    // Act: AND filter with comma-separated list
    $resp = $this->get('/stories?genres=' . $horror->slug . ',' . $comedy->slug);

    // Assert: only the story that has BOTH appears
    $resp->assertOk();
    $resp->assertSee('Horror Comedy');
    $resp->assertDontSee('Just Horror');
    $resp->assertDontSee('Just Comedy');
    $resp->assertDontSee('Only Drama');
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
    $resp->assertSee('story::shared.by');
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

    // Community story (should appear)
    $community = communityStory('Community Story', $author->id, [
        'description' => '<p>Community not hidden</p>',
    ]);

    // Private story (should not appear)
    privateStory('Private Story', $author->id, [
        'description' => '<p>Hidden</p>',
    ]);

    // Act
    $resp = $this->actingAs(bob($this))->get('/stories');

    // Assert
    $resp->assertOk();
    $resp->assertSee($public->title);
    $resp->assertSee('story::shared.by');
    $resp->assertSee($community->title);
    $resp->assertDontSee('Private Story');
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

it('filters stories by type slug', function () {
    // Arrange
    $author = alice($this);

    $theater = makeStoryType('Theater');
    $poem = makeStoryType('Poem');

    publicStory('Theater Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_type_id' => $theater->id,
    ]);

    publicStory('Poem Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_type_id' => $poem->id,
    ]);

    // Act: filter by novel slug
    $resp = $this->get('/stories?type=' . $theater->slug);

    // Assert: only novel story visible
    $resp->assertOk();
    $resp->assertSee('Theater Story');
    $resp->assertDontSee('Poem Story');
    // UI bits
    $resp->assertSee(trans('story::index.filter'));
    $resp->assertSee(trans('story::index.reset_filters'));
});

it('filters stories by audience slug', function () {
    // Arrange
    $author = alice($this);

    $teens = makeAudience('Teens');
    $adults = makeAudience('Adults');

    publicStory('Teen Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_audience_id' => $teens->id,
    ]);

    publicStory('Adult Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_audience_id' => $adults->id,
    ]);

    // Act: filter by teens audience slug
    $resp = $this->get('/stories?audiences=' . $teens->slug);

    // Assert: only teen story visible
    $resp->assertOk();
    $resp->assertSee('Teen Story');
    $resp->assertDontSee('Adult Story');
    // UI bits
    $resp->assertSee(trans('story::index.filter'));
    $resp->assertSee(trans('story::index.reset_filters'));
});
