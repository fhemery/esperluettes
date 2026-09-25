<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryPreferenceService;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * StoryPreferenceService is a container singleton memoized for the whole
 * request, and the container is NOT rebuilt between two HTTP calls inside one
 * test — hence the `forgetInstance` right after every `setSettingsValue`.
 */

beforeEach(function () {
    Cache::flush();

    $this->author = alice($this);
    $this->reader = bob($this);

    $violence = makeRefTriggerWarning('Violence');

    $this->noTw = publicStory('Only No TW', $this->author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [],
    ]);
    $this->noTw->tw_disclosure = Story::TW_NO_TW;
    $this->noTw->saveQuietly();
    createPublishedChapter($this, $this->noTw, $this->author);

    $this->listed = publicStory('Listed TW', $this->author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [$violence->id],
    ]);
    $this->listed->tw_disclosure = Story::TW_LISTED;
    $this->listed->saveQuietly();
    createPublishedChapter($this, $this->listed, $this->author);

    $this->unspoiled = publicStory('Unspoiled Tale', $this->author->id, [
        'description' => '<p>Desc</p>',
        'story_ref_trigger_warning_ids' => [],
    ]);
    $this->unspoiled->tw_disclosure = Story::TW_UNSPOILED;
    $this->unspoiled->saveQuietly();
    createPublishedChapter($this, $this->unspoiled, $this->author);
});

function hideStoriesWithTwFor(int $userId): void
{
    setSettingsValue(
        $userId,
        StoryServiceProvider::TAB_STORIES,
        StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
        true,
    );
    app()->forgetInstance(StoryPreferenceService::class);
}

describe('Library /stories and the hide-stories-with-tw preference', function () {
    it('hides listed and unspoiled stories from /stories when the preference is on', function () {
        hideStoriesWithTwFor($this->reader->id);

        $resp = $this->actingAs($this->reader)->get('/stories');

        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertDontSee('Listed TW');
        $resp->assertDontSee('Unspoiled Tale');
    });

    it('leaves /stories unfiltered when the preference is off', function () {
        $resp = $this->actingAs($this->reader)->get('/stories');

        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertSee('Listed TW');
        $resp->assertSee('Unspoiled Tale');
    });

    it('does not tick the no_tw_only checkbox when only the preference is on', function () {
        hideStoriesWithTwFor($this->reader->id);

        $resp = $this->actingAs($this->reader)->get('/stories');

        $resp->assertOk();
        // The list is filtered...
        $resp->assertDontSee('Listed TW');
        // ...but the checkbox stays untouched and the preference never leaks
        // into the pagination query string.
        expect($resp->viewData('currentNoTwOnly'))->toBeFalse();

        $appends = (fn () => $this->appends)->call($resp->viewData('viewModel'));
        expect($appends)->not->toHaveKey('no_tw_only');
    });

    it('behaves identically when the checkbox and the preference are both on', function () {
        hideStoriesWithTwFor($this->reader->id);

        $resp = $this->actingAs($this->reader)->get('/stories?no_tw_only=1');

        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertDontSee('Listed TW');
        $resp->assertDontSee('Unspoiled Tale');
        expect($resp->viewData('currentNoTwOnly'))->toBeTrue();
    });

    it('still filters with the checkbox alone when the preference is off', function () {
        $resp = $this->actingAs($this->reader)->get('/stories?no_tw_only=1');

        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertDontSee('Listed TW');
        $resp->assertDontSee('Unspoiled Tale');
    });

    it('does not filter /stories for a guest', function () {
        $resp = $this->get('/stories');

        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertSee('Listed TW');
        $resp->assertSee('Unspoiled Tale');
    });

    it('hides the reader own trigger-warned story from /stories', function () {
        // No author exemption: the author browsing the library with the
        // preference on does not see their own trigger-warned stories.
        hideStoriesWithTwFor($this->author->id);

        $resp = $this->actingAs($this->author)->get('/stories');

        $resp->assertOk();
        $resp->assertSee('Only No TW');
        $resp->assertDontSee('Listed TW');
        $resp->assertDontSee('Unspoiled Tale');
    });
});

describe('Surfaces the library preference must not touch', function () {
    it('does not filter the profile stories tab when the preference is on', function () {
        hideStoriesWithTwFor($this->reader->id);

        $this->actingAs($this->reader);
        $html = Blade::render(
            '<x-story::profile-stories-component :owner-user-id="$userId" />',
            ['userId' => $this->author->id],
        );

        expect($html)
            ->toContain('Only No TW')
            ->toContain('Listed TW')
            ->toContain('Unspoiled Tale');
    });

    it('does not filter StoryPublicApi::listStories when the preference is on', function () {
        hideStoriesWithTwFor($this->reader->id);

        $this->actingAs($this->reader);
        $result = app(StoryPublicApi::class)->listStories();

        $titles = array_map(fn ($dto) => $dto->title, $result->data);
        expect($titles)->toContain('Only No TW', 'Listed TW', 'Unspoiled Tale');
    });
});
