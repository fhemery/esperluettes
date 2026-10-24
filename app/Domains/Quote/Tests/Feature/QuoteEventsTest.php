<?php

use App\Domains\Quote\Public\Events\ChapterPassageQuoted;
use App\Domains\Quote\Public\Events\ChapterPassageQuoteDeleted;
use App\Domains\Quote\Public\Events\UserQuotesRemoved;
use App\Domains\Quote\Public\Events\UserQuotesRestored;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('quote event registration', function () {
    it('stores and rehydrates ChapterPassageQuoted when a reader quotes', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $this->actingAs($reader)->postJson('/quotes', [
            'chapter_id' => $chapter->id,
            'story_id' => $story->id,
            'highlighted_text' => 'A passage',
        ])->assertStatus(201);

        $event = latestEventOf('Quote.ChapterPassageQuoted', ChapterPassageQuoted::class);

        expect($event)->not->toBeNull();
        expect($event->quoterId)->toBe($reader->id);
        expect($event->chapterId)->toBe($chapter->id);
        expect($event->storyId)->toBe($story->id);
    });

    it('round-trips ChapterPassageQuoteDeleted through the stored event', function () {
        dispatchEvent(new ChapterPassageQuoteDeleted(quoterId: 3, chapterId: 4, storyId: 5));

        $event = latestEventOf('Quote.ChapterPassageQuoteDeleted', ChapterPassageQuoteDeleted::class);

        expect($event)->not->toBeNull();
        expect($event->toPayload())->toBe(['quoterId' => 3, 'chapterId' => 4, 'storyId' => 5]);
    });

    it('round-trips UserQuotesRemoved through the stored event', function () {
        dispatchEvent(new UserQuotesRemoved(7, 2));

        $event = latestEventOf('Quote.UserQuotesRemoved', UserQuotesRemoved::class);

        expect($event)->not->toBeNull();
        expect($event->toPayload())->toBe(['userId' => 7, 'count' => 2]);
    });

    it('round-trips UserQuotesRestored through the stored event', function () {
        dispatchEvent(new UserQuotesRestored(7, 2));

        $event = latestEventOf('Quote.UserQuotesRestored', UserQuotesRestored::class);

        expect($event)->not->toBeNull();
        expect($event->toPayload())->toBe(['userId' => 7, 'count' => 2]);
    });
});
