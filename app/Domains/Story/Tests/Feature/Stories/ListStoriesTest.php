<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Services\ChapterCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

// Note: this test is basically checking that the mapping is done correctly.
// Business rules are tests in deep length in ListStoriesPublicApiTest

describe('Card display', function () {
    it('displays genre badges on list cards', function () {
        // Arrange
        $author = alice($this);
        $fantasy = makeGenre('Fantasy');
        $mystery = makeGenre('Mystery');

        $genreful = publicStory('Genreful Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_genre_ids' => [$fantasy->id, $mystery->id],
        ]);
        createPublishedChapter($this, $genreful, $author);

        // Act
        $resp = $this->get('/stories');

        // Assert: badges show both genre names
        $resp->assertOk();
        $resp->assertSee('Genreful Story');
        $resp->assertSee('Fantasy');
        $resp->assertSee('Mystery');
    });

    describe('Trigger warnings', function () {

        it('displays trigger warning badges on list cards', function () {
            // Arrange
            $author = alice($this);
            $violence = makeTriggerWarning('Violence');
            $abuse = makeTriggerWarning('Abuse');

            $twStory = publicStory('TW Story', $author->id, [
                'description' => '<p>Desc</p>',
                'story_ref_trigger_warning_ids' => [$violence->id, $abuse->id],
            ]);
            createPublishedChapter($this, $twStory, $author);

            // Act
            $resp = $this->get('/stories');

            // Assert: shows both TW names as badges in the list
            $resp->assertOk();
            $resp->assertSee('TW Story');
            $resp->assertSee('Violence');
            $resp->assertSee('Abuse');
        });

        it('shows a green check icon on list cards when tw_disclosure is no_tw', function () {
            // Arrange
            $author = alice($this);
            $clean = publicStory('No TW Story', $author->id, [
                'description' => '<p>Desc</p>',
                'story_ref_trigger_warning_ids' => [],
            ]);
            // Explicitly set disclosure to no_tw
            $clean->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_NO_TW;
            $clean->saveQuietly();
            createPublishedChapter($this, $clean, $author);

            // Act
            $resp = $this->get('/stories');

            // Assert: check icon and tooltip
            $resp->assertOk();
            $resp->assertSee('No TW Story');
            $resp->assertSee('warning_off');
            $resp->assertSee(trans('story::shared.trigger_warnings.tooltips.no_tw'));
        });

        it('shows an orange help icon on list cards when tw_disclosure is unspoiled', function () {
            // Arrange
            $author = alice($this);
            $mystery = publicStory('Unspoiled Story', $author->id, [
                'description' => '<p>Desc</p>',
                'story_ref_trigger_warning_ids' => [],
            ]);
            // Explicitly set disclosure to unspoiled
            $mystery->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_UNSPOILED;
            $mystery->saveQuietly();
            createPublishedChapter($this, $mystery, $author);

            // Act
            $resp = $this->get('/stories');

            // Assert: help icon and tooltip (careful: assert the specific TW tooltip text)
            $resp->assertOk();
            $resp->assertSee('Unspoiled Story');
            $resp->assertSee('help');
            $resp->assertSee(trans('story::shared.trigger_warnings.tooltips.unspoiled'));
        });
    });
});

describe('Filtering', function () {
    it('filters stories by a single genre slug', function () {
        // Arrange
        $author = alice($this);
        $romance = makeGenre('Romance');
        $sciFi = makeGenre('Sci Fi');

        $romanceStory = publicStory('Romance Tale', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_genre_ids' => [$romance->id],
        ]);
        createPublishedChapter($this, $romanceStory, $author);

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

    it('excludes stories having a selected trigger warning via exclude_tw', function () {
        // Arrange
        $author = alice($this);
        $violence = makeTriggerWarning('Violence');
        $abuse = makeTriggerWarning('Abuse');

        $clean = publicStory('Clean Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_trigger_warning_ids' => [],
        ]);
        createPublishedChapter($this, $clean, $author);

        $violent = publicStory('Violent Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_trigger_warning_ids' => [$violence->id],
        ]);
        createPublishedChapter($this, $violent, $author);

        $abusive = publicStory('Abusive Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_trigger_warning_ids' => [$abuse->id],
        ]);
        createPublishedChapter($this, $abusive, $author);

        // Act: exclude violence
        $resp = $this->get('/stories?exclude_tw=' . $violence->slug);

        // Assert: anything tagged with Violence is excluded
        $resp->assertOk();
        $resp->assertSee('Clean Story');
        $resp->assertSee('Abusive Story');
        $resp->assertDontSee('Violent Story');
    });

    it('filters to only explicit No TW stories when no_tw_only=1', function () {
        // Arrange
        $author = alice($this);
        $violence = makeTriggerWarning('Violence');

        // No TW story
        $noTw = publicStory('Only No TW', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_trigger_warning_ids' => [],
        ]);
        $noTw->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_NO_TW;
        $noTw->saveQuietly();
        createPublishedChapter($this, $noTw, $author);

        // Listed with TW
        $listed = publicStory('Listed TW', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_trigger_warning_ids' => [$violence->id],
        ]);
        $listed->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_LISTED;
        $listed->saveQuietly();
        createPublishedChapter($this, $listed, $author);

        // Unspoiled
        $unspoiled = publicStory('Unspoiled', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_trigger_warning_ids' => [],
        ]);
        $unspoiled->tw_disclosure = \App\Domains\Story\Private\Models\Story::TW_UNSPOILED;
        $unspoiled->saveQuietly();
        createPublishedChapter($this, $unspoiled, $author);

        // Act
        $resp = $this->get('/stories?no_tw_only=1');

        // Assert: only the No TW story is visible
        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertDontSee('Listed TW');
        $resp->assertDontSee('Unspoiled');
    });

    it('filters stories by multiple genre slugs (AND semantics)', function () {
        // Arrange
        $author = alice($this);
        $horror = makeGenre('Horror');
        $comedy = makeGenre('Comedy');
        $drama = makeGenre('Drama');

        // Matches both Horror AND Comedy
        $hc = publicStory('Horror Comedy', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_genre_ids' => [$horror->id, $comedy->id],
        ]);
        createPublishedChapter($this, $hc, $author);

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

    it('filters stories by type slug', function () {
        // Arrange
        $author = alice($this);

        $theater = makeStoryType('Theater');
        $poem = makeStoryType('Poem');

        $theaterStory = publicStory('Theater Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_type_id' => $theater->id,
        ]);
        createPublishedChapter($this, $theaterStory, $author);

        $poemStory = publicStory('Poem Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_type_id' => $poem->id,
        ]);
        createPublishedChapter($this, $poemStory, $author);

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

        $teenStory = publicStory('Teen Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_audience_id' => $teens->id,
        ]);
        createPublishedChapter($this, $teenStory, $author);

        $adultStory = publicStory('Adult Story', $author->id, [
            'description' => '<p>Desc</p>',
            'story_ref_audience_id' => $adults->id,
        ]);
        createPublishedChapter($this, $adultStory, $author);

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
});

describe('Story access', function () {
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
        createPublishedChapter($this, $public, $author);

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
        createPublishedChapter($this, $public, $author);

        // Community story (should appear)
        $community = communityStory('Community Story', $author->id, [
            'description' => '<p>Community not hidden</p>',
        ]);
        createPublishedChapter($this, $community, $author);

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
        createPublishedChapter($this, $public, $author);

        // Community story (should not appear)
        communityStory('Community Story For User', $author->id, [
            'description' => '<p>Hidden</p>',
        ]);

        // Private story (should not appear)
        privateStory('Private Story For User', $author->id, [
            'description' => '<p>Hidden</p>',
        ]);

        // Logged-in user with role 'user' (not user-confirmed)
        $regularUser = bob($this, roles: [Roles::USER]);

        // Act
        $resp = $this->actingAs($regularUser)->get('/stories');

        // Assert
        $resp->assertOk();
        $resp->assertSee($public->title);
        $resp->assertDontSee('Community Story For User');
        $resp->assertDontSee('Private Story For User');
    });

    it('hides public stories without any published chapter', function () {
        // Arrange
        $author = alice($this);

        // Should be shown (has a published chapter)
        $withChapter = publicStory('With Chapter', $author->id, [
            'description' => '<p>Desc</p>',
        ]);
        createPublishedChapter($this, $withChapter, $author);

        // Should be hidden (no chapters)
        publicStory('No Chapter', $author->id, [
            'description' => '<p>Desc</p>',
        ]);

        // Act
        $resp = $this->get('/stories');

        // Assert: only story with a published chapter is visible
        $resp->assertOk();
        $resp->assertSee('With Chapter');
        $resp->assertDontSee('No Chapter');
    });
});

describe('Ordering and Pagination', function () {
    it('paginates 12 stories ordered by last published chapter date desc', function () {
        // Arrange
        $author = alice($this);

        // Create 20 public stories, each with a published chapter and set last_chapter_published_at for ordering
        // We need to increase the chapter counter of the user
        $chapterCreditService = app(ChapterCreditService::class);

        for ($i = 1; $i <= 20; $i++) {
            $story = publicStory(sprintf('Story %02d', $i), $author->id, [
                'description' => '<p>Desc</p>',
            ]);
            $chapterCreditService->grantOne($author->id);
            createPublishedChapter($this, $story, $author);
            // Set synthetic ordering based on last_chapter_published_at (newest first should be Story 20)
            $story->last_chapter_published_at = now()->subMinutes(60 - $i);
            $story->saveQuietly();
        }

        // Act: first page
        $page1 = $this->get('/stories');

        // Assert: shows newest first (Story 20 by last_chapter_published_at) and does not include oldest beyond 24 (Story 06)
        $page1->assertOk();
        $page1->assertSee('Story 20');
        $page1->assertDontSee('Story 06');
        $page1->assertSee('/stories?page=2'); // pagination link to next page

        // Act: second page
        $page2 = $this->get('/stories?page=2');

        // Assert: includes older ones
        $page2->assertOk();
        $page2->assertSee('Story 06');
        $page2->assertSee('Story 01');
    });
});

describe('Reading statistics', function () {
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

    it('shows total words on stories index cards', function () {
        $author = alice($this);
        $story = publicStory('Index Total Words', $author->id);

        // Create two published chapters with known word counts: 2 + 3 = 5
        createPublishedChapter($this, $story, $author, ['content' => '<p>one two</p>']);
        createPublishedChapter($this, $story, $author, ['content' => '<p>three four five</p>']);

        // Guest view is fine
        Auth::logout();
        $resp = $this->get('/stories');
        $resp->assertOk();
        $resp->assertSee('Index Total Words');
        $resp->assertSee('5');
    });

    it('shows published chapters count on stories index cards', function () {
        $author = alice($this);
        $story = publicStory('Index Chapters Count', $author->id);

        // Create two published chapters
        createPublishedChapter($this, $story, $author, ['title' => 'C1']);
        createPublishedChapter($this, $story, $author, ['title' => 'C2']);

        Auth::logout();
        $resp = $this->get('/stories');
        $resp->assertOk();
        $resp->assertSee('Index Chapters Count');
        $resp->assertSee('2');
    });
});
