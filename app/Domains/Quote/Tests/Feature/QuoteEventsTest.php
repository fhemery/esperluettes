<?php

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserReactivated;
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

describe('quote deletion and lifecycle events', function () {
    function quoteSetup($test): array
    {
        $author = alice($test);
        $reader = bob($test);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($test, $story, $author);

        return [$author, $reader, $story, $chapter];
    }

    it('emits ChapterPassageQuoteDeleted when the owner deletes a quote', function () {
        [, $reader, $story, $chapter] = quoteSetup($this);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($reader)->deleteJson("/quotes/{$quote->id}")->assertSuccessful();

        $event = latestEventOf('Quote.ChapterPassageQuoteDeleted', ChapterPassageQuoteDeleted::class);
        expect($event)->not->toBeNull();
        expect($event->toPayload())->toBe(['quoterId' => $reader->id, 'chapterId' => $chapter->id, 'storyId' => $story->id]);
    });

    it('emits nothing when a non-owner fails to delete a quote', function () {
        [$author, $reader, $story, $chapter] = quoteSetup($this);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        $this->actingAs($author)->deleteJson("/quotes/{$quote->id}")->assertStatus(403);

        expect(countEvents('Quote.ChapterPassageQuoteDeleted'))->toBe(0);
    });

    it('emits UserQuotesRemoved with the visible count on deactivation', function () {
        [, $reader, $story, $chapter] = quoteSetup($this);
        createQuote($reader->id, $chapter->id, $story->id);
        createQuote($reader->id, $chapter->id, $story->id);
        createQuote($reader->id, $chapter->id, $story->id)->delete();

        event(new UserDeactivated($reader->id));

        $event = latestEventOf('Quote.UserQuotesRemoved', UserQuotesRemoved::class);
        expect($event->toPayload())->toBe(['userId' => $reader->id, 'count' => 2]);
    });

    it('emits no UserQuotesRemoved when a deactivated user has no quote', function () {
        [, $reader] = quoteSetup($this);

        event(new UserDeactivated($reader->id));

        expect(countEvents('Quote.UserQuotesRemoved'))->toBe(0);
    });

    it('emits UserQuotesRestored without counting a quote the reader deleted', function () {
        [, $reader, $story, $chapter] = quoteSetup($this);
        $first = createQuote($reader->id, $chapter->id, $story->id);
        createQuote($reader->id, $chapter->id, $story->id);
        createQuote($reader->id, $chapter->id, $story->id);
        $this->actingAs($reader)->deleteJson("/quotes/{$first->id}")->assertSuccessful();

        event(new UserDeactivated($reader->id));
        event(new UserReactivated($reader->id));

        $event = latestEventOf('Quote.UserQuotesRestored', UserQuotesRestored::class);
        expect($event->toPayload())->toBe(['userId' => $reader->id, 'count' => 2]);
    });

    it('emits no UserQuotesRestored when nothing is restored', function () {
        [, $reader] = quoteSetup($this);

        event(new UserDeactivated($reader->id));
        event(new UserReactivated($reader->id));

        expect(countEvents('Quote.UserQuotesRestored'))->toBe(0);
    });

    it('emits UserQuotesRemoved with the visible count when an active user is deleted', function () {
        [, $reader, $story, $chapter] = quoteSetup($this);
        foreach (range(1, 3) as $i) {
            createQuote($reader->id, $chapter->id, $story->id);
        }

        event(new UserDeleted($reader->id));

        $event = latestEventOf('Quote.UserQuotesRemoved', UserQuotesRemoved::class);
        expect($event->toPayload())->toBe(['userId' => $reader->id, 'count' => 3]);
    });

    it('emits no second UserQuotesRemoved when a user is deleted after deactivation', function () {
        [, $reader, $story, $chapter] = quoteSetup($this);
        createQuote($reader->id, $chapter->id, $story->id);
        createQuote($reader->id, $chapter->id, $story->id);

        event(new UserDeactivated($reader->id));
        event(new UserDeleted($reader->id));

        expect(countEvents('Quote.UserQuotesRemoved'))->toBe(1);
    });
});
