<?php

use App\Domains\ReadList\Public\Events\StoryAddedToReadList;
use App\Domains\ReadList\Public\Events\StoryRemovedFromReadList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('ReadList events emission', function () {
    it('emits StoryAddedToReadList when a story is added', function () {
        $author = alice($this);
        $story = publicStory('E1', $author->id);
        $reader = bob($this);

        $this->actingAs($reader);
        addToReadList($this, $story->id)->assertRedirect();

        $event = latestEventOf(StoryAddedToReadList::name(), StoryAddedToReadList::class);
        expect($event)->not->toBeNull();
        expect($event->userId)->toBe($reader->id);
        expect($event->storyId)->toBe($story->id);
    });

    it('does not emit StoryAddedToReadList on duplicate add (no-op)', function () {
        $author = alice($this);
        $story = publicStory('E2', $author->id);
        $reader = bob($this);

        $this->actingAs($reader);
        addToReadList($this, $story->id)->assertRedirect();
        addToReadList($this, $story->id)->assertRedirect();

        $count = countEvents(StoryAddedToReadList::name());
        expect($count)->toBe(1);
    });

    it('emits StoryRemovedFromReadList when a story is removed', function () {
        $author = alice($this);
        $story = publicStory('E3', $author->id);
        $reader = bob($this);

        $this->actingAs($reader);
        addToReadList($this, $story->id)->assertRedirect();

        // Remove
        $this->delete(route('readlist.remove', $story->id))->assertRedirect();

        $event = latestEventOf(StoryRemovedFromReadList::name(), StoryRemovedFromReadList::class);
        expect($event)->not->toBeNull();
        expect($event->userId)->toBe($reader->id);
        expect($event->storyId)->toBe($story->id);
    });

    it('does not emit StoryRemovedFromReadList when nothing was removed', function () {
        $author = alice($this);
        $story = publicStory('E4', $author->id);
        $reader = bob($this);

        $this->actingAs($reader);
        // Remove without prior add
        $this->delete(route('readlist.remove', $story->id))->assertRedirect();

        $count = countEvents(StoryRemovedFromReadList::name());
        expect($count)->toBe(0);
    });
});
