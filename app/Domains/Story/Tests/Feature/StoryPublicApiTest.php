<?php

use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryPublicApi::getStoryIdByChapterId', function () {
    it('returns the story id of an existing chapter', function () {
        $author = alice($this);
        $story = publicStory('S', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $api = app(StoryPublicApi::class);

        expect($api->getStoryIdByChapterId($chapter->id))->toBe($story->id);
    });

    it('returns null for an unknown chapter id', function () {
        $api = app(StoryPublicApi::class);

        expect($api->getStoryIdByChapterId(999999))->toBeNull();
    });
});
