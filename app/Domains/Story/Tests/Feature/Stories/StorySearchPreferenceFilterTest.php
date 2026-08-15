<?php

declare(strict_types=1);

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryPreferenceService;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Providers\StoryServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    $this->author = alice($this);
    $this->reader = bob($this);

    $violence = makeRefTriggerWarning('Violence');

    publicStory('Quest No TW', $this->author->id, [
        'tw_disclosure' => Story::TW_NO_TW,
    ]);
    publicStory('Quest Listed', $this->author->id, [
        'tw_disclosure' => Story::TW_LISTED,
        'story_ref_trigger_warning_ids' => [$violence->id],
    ]);
    publicStory('Quest Unspoiled', $this->author->id, [
        'tw_disclosure' => Story::TW_UNSPOILED,
    ]);
});

function searchTitlesFor(?int $viewerId): array
{
    $result = app(StoryPublicApi::class)->searchStories('Quest', $viewerId);

    return [
        'titles' => array_map(fn ($dto) => $dto->title, $result['items']),
        'total' => $result['total'],
    ];
}

function hideStoriesWithTwForSearch(int $userId): void
{
    setSettingsValue(
        $userId,
        StoryServiceProvider::TAB_STORIES,
        StoryServiceProvider::KEY_HIDE_STORIES_WITH_TW,
        true,
    );
    app()->forgetInstance(StoryPreferenceService::class);
}

describe('StoryPublicApi::searchStories and the hide-stories-with-tw preference', function () {
    it('returns only no_tw stories to a viewer whose preference is on', function () {
        hideStoriesWithTwForSearch($this->reader->id);
        $this->actingAs($this->reader);

        $result = searchTitlesFor($this->reader->id);

        expect($result['titles'])->toBe(['Quest No TW']);
        // The count must be filtered too, or the dropdown claims more results
        // than it can show.
        expect($result['total'])->toBe(1);
    });

    it('returns every matching story when the preference is off', function () {
        $this->actingAs($this->reader);

        $result = searchTitlesFor($this->reader->id);

        expect($result['titles'])->toContain('Quest No TW', 'Quest Listed', 'Quest Unspoiled');
        expect($result['total'])->toBe(3);
    });

    it('returns every matching story when no viewer id is given', function () {
        // Guest path: the preference of whoever is logged in must not leak in
        // through a null viewer id.
        hideStoriesWithTwForSearch($this->reader->id);
        $this->actingAs($this->reader);

        $result = searchTitlesFor(null);

        expect($result['titles'])->toContain('Quest No TW', 'Quest Listed', 'Quest Unspoiled');
        expect($result['total'])->toBe(3);
    });
});
