<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryPreferenceService;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('RandomStoriesComponent', function () {
    it('renders public stories for non-confirmed users and excludes authored stories', function () {
        $author = alice($this);
        // Eligible public story by another author
        $public = publicStory('Public Discoverable', $author->id);
        createPublishedChapter($this, $public, $author, ['title' => 'C1']);
        // Community story by another author (should be hidden to non-confirmed)
        $community = communityStory('Community Discover', $author->id);
        createPublishedChapter($this, $community, $author, ['title' => 'C1']);

        // Create a story authored by the viewer (must be excluded)
        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);
        $own = publicStory('My Own Story', $viewer->id);
        createPublishedChapter($this, $own, $viewer, ['title' => 'C1']);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain(__('story::discover.title'))
            ->toContain('Public Discoverable')
            ->not->toContain('Community Discover')
            ->not->toContain('My Own Story')
            ->toContain(__('story::discover.placeholder_cta'));
    });

    it('includes community stories for confirmed users', function () {
        $author = alice($this);
        // Eligible stories by another author
        $public = publicStory('Public Discoverable 2', $author->id);
        createPublishedChapter($this, $public, $author, ['title' => 'C1']);
        $community = communityStory('Community Discover 2', $author->id);
        createPublishedChapter($this, $community, $author, ['title' => 'C1']);

        $viewer = carol($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($viewer);
        // Own story should be excluded even if public
        $own = publicStory('My Confirmed Own Story', $viewer->id);
        createPublishedChapter($this, $own, $viewer, ['title' => 'C1']);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain('Public Discoverable 2')
            ->toContain('Community Discover 2')
            ->not->toContain('My Confirmed Own Story');
    });

    it('includes an empty placeholder', function () {
        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain(__('story::discover.title'))
            ->toContain(__('story::discover.placeholder_cta'));
    });

    it('excludes trigger-warned stories from the discover carousel when the preference is on', function () {
        $author = alice($this);
        $noTw = publicStory('Discover No TW', $author->id, ['tw_disclosure' => Story::TW_NO_TW]);
        createPublishedChapter($this, $noTw, $author, ['title' => 'C1']);
        $listed = publicStory('Discover Listed TW', $author->id, ['tw_disclosure' => Story::TW_LISTED]);
        createPublishedChapter($this, $listed, $author, ['title' => 'C1']);
        $unspoiled = publicStory('Discover Unspoiled TW', $author->id, ['tw_disclosure' => Story::TW_UNSPOILED]);
        createPublishedChapter($this, $unspoiled, $author, ['title' => 'C1']);

        $viewer = bob($this, roles: [Roles::USER]);
        setSettingsValue(
            $viewer->id,
            StoryServiceProvider::TAB_STORIES,
            StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
            true,
        );
        app()->forgetInstance(StoryPreferenceService::class);
        $this->actingAs($viewer);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain('Discover No TW')
            ->not->toContain('Discover Listed TW')
            ->not->toContain('Discover Unspoiled TW');
    });

    it('keeps them when the preference is off', function () {
        $author = alice($this);
        $noTw = publicStory('Discover No TW', $author->id, ['tw_disclosure' => Story::TW_NO_TW]);
        createPublishedChapter($this, $noTw, $author, ['title' => 'C1']);
        $listed = publicStory('Discover Listed TW', $author->id, ['tw_disclosure' => Story::TW_LISTED]);
        createPublishedChapter($this, $listed, $author, ['title' => 'C1']);
        $unspoiled = publicStory('Discover Unspoiled TW', $author->id, ['tw_disclosure' => Story::TW_UNSPOILED]);
        createPublishedChapter($this, $unspoiled, $author, ['title' => 'C1']);

        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain('Discover No TW')
            ->toContain('Discover Listed TW')
            ->toContain('Discover Unspoiled TW');
    });

    it('only includes stories with at least one published chapter', function () {
        $author = alice($this);

        // Valid: public story with a published chapter
        $valid = publicStory('With Published', $author->id);
        createPublishedChapter($this, $valid, $author, ['title' => 'C1']);

        // Invalid: public story with NO chapters
        $noChapters = publicStory('No Chapters', $author->id);

        // Invalid: public story with only unpublished chapters
        $draftOnly = publicStory('Only Drafts', $author->id);
        createUnpublishedChapter($this, $draftOnly, $author, ['title' => 'D1']);

        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain('With Published')
            ->not->toContain('No Chapters')
            ->not->toContain('Only Drafts');
    });

    it('excludes stories in which the viewer has marked a chapter as read', function () {
        $author = alice($this);
        $started = publicStory('Started Story', $author->id);
        $c1 = createPublishedChapter($this, $started, $author, ['title' => 'C1']);
        createPublishedChapter($this, $started, $author, ['title' => 'C2']);
        $fresh = publicStory('Fresh Story', $author->id);
        createPublishedChapter($this, $fresh, $author, ['title' => 'C1']);

        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);
        markAsRead($this, $c1)->assertNoContent();

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain('Fresh Story')
            ->not->toContain('Started Story');
    });

    it('still shows a story another user has marked as read', function () {
        $author = alice($this);
        $story = publicStory('Read By Someone Else', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'C1']);

        $other = carol($this);
        $this->actingAs($other);
        markAsRead($this, $chapter)->assertNoContent();

        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)->toContain('Read By Someone Else');
    });

    it('still shows a story in the viewer read list with no chapter read', function () {
        $author = alice($this);
        $story = publicStory('In Read List', $author->id);
        createPublishedChapter($this, $story, $author, ['title' => 'C1']);

        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);
        addToReadList($this, $story->id);

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)->toContain('In Read List');
    });

    it('shows a story again once the viewer unmarks every chapter', function () {
        $author = alice($this);
        $story = publicStory('Unmarked Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'C1']);

        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);
        markAsRead($this, $chapter)->assertNoContent();
        markAsUnread($this, $chapter)->assertNoContent();

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)->toContain('Unmarked Story');
    });

    it('renders only the placeholder when every eligible story has been read', function () {
        $author = alice($this);
        $story = publicStory('Only Story Read', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'C1']);

        $viewer = bob($this, roles: [Roles::USER]);
        $this->actingAs($viewer);
        markAsRead($this, $chapter)->assertNoContent();

        $html = Blade::render('<x-story::random-stories-component />');
        expect($html)
            ->toContain(__('story::discover.placeholder_cta'))
            ->not->toContain('Only Story Read');
    });
});
