<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

it('displays trigger warning badges on list cards', function () {
    // Arrange
    $author = alice($this);
    $violence = makeTriggerWarning('Violence');
    $abuse = makeTriggerWarning('Abuse');

    publicStory('TW Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$violence->id, $abuse->id],
    ]);

    // Act
    $resp = $this->get('/stories');

    // Assert: shows both TW names as badges in the list
    $resp->assertOk();
    $resp->assertSee('TW Story');
    $resp->assertSee('Violence');
    $resp->assertSee('Abuse');
});

it('excludes stories having a selected trigger warning via exclude_tw', function () {
    // Arrange
    $author = alice($this);
    $violence = makeTriggerWarning('Violence');
    $abuse = makeTriggerWarning('Abuse');

    publicStory('Clean Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [],
    ]);

    publicStory('Violent Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$violence->id],
    ]);

    publicStory('Abusive Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$abuse->id],
    ]);

    // Act: exclude violence
    $resp = $this->get('/stories?exclude_tw=' . $violence->slug);

    // Assert: anything tagged with Violence is excluded
    $resp->assertOk();
    $resp->assertSee('Clean Story');
    $resp->assertSee('Abusive Story');
    $resp->assertDontSee('Violent Story');
});

it('excludes stories having any of multiple selected trigger warnings (OR)', function () {
    // Arrange
    $author = alice($this);
    $violence = makeTriggerWarning('Violence');
    $abuse = makeTriggerWarning('Abuse');
    $drugs = makeTriggerWarning('Drugs');

    publicStory('Only Violence', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$violence->id],
    ]);

    publicStory('Only Abuse', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$abuse->id],
    ]);

    publicStory('Only Drugs', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$drugs->id],
    ]);

    publicStory('Clean Story', $author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [],
    ]);

    // Act: exclude violence and abuse (comma separated)
    $resp = $this->get('/stories?exclude_tw=' . $violence->slug . ',' . $abuse->slug);

    // Assert: stories having either of the excluded TWs disappear; others remain
    $resp->assertOk();
    $resp->assertDontSee('Only Violence');
    $resp->assertDontSee('Only Abuse');
    $resp->assertSee('Only Drugs');
    $resp->assertSee('Clean Story');
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

it('should show public and community stories to logged users with role user-confirmed', function () {
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

it('should only show public stories to logged users with role user', function () {
    // Arrange
    $author = alice($this);

    // Public story (should appear)
    $public = publicStory('Public Story For User', $author->id, [
        'description' => '<p>Desc</p>',
    ]);

    // Community story (should not appear)
    communityStory('Community Story For User', $author->id, [
        'description' => '<p>Hidden</p>',
    ]);

    // Private story (should not appear)
    privateStory('Private Story For User', $author->id, [
        'description' => '<p>Hidden</p>',
    ]);

    // Logged-in user with role 'user' (not user-confirmed)
    $regularUser = bob($this, roles: ['user']);

    // Act
    $resp = $this->actingAs($regularUser)->get('/stories');

    // Assert
    $resp->assertOk();
    $resp->assertSee($public->title);
    $resp->assertDontSee('Community Story For User');
    $resp->assertDontSee('Private Story For User');
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

describe('Reading statistics', function() {
    it('shows total reads on stories index cards', function () {
        $author = alice($this);
        $story = publicStory('Index Total Reads', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $reader = bob($this);
        $this->actingAs($reader);
        markAsRead($this, $chapter)->assertNoContent();

        Auth::logout();
        $resp = $this->get('/stories');
        $resp->assertOk();
        $resp->assertSee('Index Total Reads');
        $resp->assertSee('1');
    });
});