<?php

use App\Domains\Quote\Public\Api\Contracts\QuoteDto;
use App\Domains\Quote\Public\Api\Contracts\QuoteListDto;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function ownerReadsApi(): QuotePublicApi
{
    return app(QuotePublicApi::class);
}

describe('QuotePublicApi::getAllForOwner', function () {
    it('returns every quote of the owner, newest first', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        $older = createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'older']);
        $newer = createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'newer']);
        DB::table('quotes')->where('id', $older->id)->update(['created_at' => now()->subDay()]);

        $list = ownerReadsApi()->getAllForOwner($reader->id);

        expect($list)->toBeInstanceOf(QuoteListDto::class);
        expect($list->viewerIsOwner)->toBeTrue();
        expect($list->canQuote)->toBeFalse();
        expect($list->page)->toBe(1);
        expect($list->totalCount)->toBe(2);
        expect(array_map(fn(QuoteDto $q) => $q->highlightedText, $list->items))
            ->toBe(['newer', 'older']);
        expect($list->items[0]->id)->toBe($newer->id);
    });

    it('returns quotes whose story is private or whose chapter is unpublished', function () {
        $author = alice($this);
        $reader = bob($this);

        $publicStoryModel = publicStory('Public Story', $author->id);
        $publicChapter = createPublishedChapter($this, $publicStoryModel, $author);

        $privateStoryModel = privateStory('Private Story', $author->id);
        $privateChapter = createPublishedChapter($this, $privateStoryModel, $author);

        $excludedStoryModel = publicStory('Excluded Story', $author->id, ['is_excluded_from_events' => true]);
        $excludedChapter = createPublishedChapter($this, $excludedStoryModel, $author);

        $draftChapter = createUnpublishedChapter($this, $publicStoryModel, $author);

        createQuote($reader->id, $publicChapter->id, $publicStoryModel->id, ['highlighted_text' => 'visible']);
        createQuote($reader->id, $privateChapter->id, $privateStoryModel->id, ['highlighted_text' => 'private']);
        createQuote($reader->id, $excludedChapter->id, $excludedStoryModel->id, ['highlighted_text' => 'excluded']);
        createQuote($reader->id, $draftChapter->id, $publicStoryModel->id, ['highlighted_text' => 'draft']);

        $list = ownerReadsApi()->getAllForOwner($reader->id);

        expect($list->totalCount)->toBe(4);
        expect(array_map(fn(QuoteDto $q) => $q->highlightedText, $list->items))
            ->toEqualCanonicalizing(['visible', 'private', 'excluded', 'draft']);
    });

    it('carries the story and chapter metadata the picker needs', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapitre premier']);
        createQuote($reader->id, $chapter->id, $story->id, ['note' => 'a private note']);

        $item = ownerReadsApi()->getAllForOwner($reader->id)->items[0];

        expect($item->storyTitle)->toBe('Story');
        expect($item->chapterTitle)->toBe('Chapitre premier');
        expect($item->note)->toBe('a private note');
        expect($item->authorProfiles)->not->toBeEmpty();
    });

    it('returns an empty list for a user with no quotes', function () {
        $reader = bob($this);

        $list = ownerReadsApi()->getAllForOwner($reader->id);

        expect($list->items)->toBe([]);
        expect($list->totalCount)->toBe(0);
        expect($list->viewerIsOwner)->toBeTrue();
        expect($list->page)->toBe(1);
    });

    it('does not return another user quotes', function () {
        $author = alice($this);
        $reader = bob($this);
        $other = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);

        createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'mine']);
        createQuote($other->id, $chapter->id, $story->id, ['highlighted_text' => 'theirs']);

        $list = ownerReadsApi()->getAllForOwner($reader->id);

        expect($list->totalCount)->toBe(1);
        expect($list->items[0]->highlightedText)->toBe('mine');
    });

    it('issues the same number of queries for 5 quotes and for 20 quotes over the same stories', function () {
        $author = alice($this);
        $small = bob($this);
        $large = carol($this);

        $chapters = [];
        for ($s = 0; $s < 5; $s++) {
            $story = publicStory('Story ' . $s, $author->id);
            $chapters[] = [createPublishedChapter($this, $story, $author), $story];
        }

        foreach ($chapters as [$chapter, $story]) {
            createQuote($small->id, $chapter->id, $story->id);
            for ($q = 0; $q < 4; $q++) {
                createQuote($large->id, $chapter->id, $story->id);
            }
        }

        $count = function (int $userId): int {
            $queries = 0;
            DB::listen(function () use (&$queries) {
                $queries++;
            });
            ownerReadsApi()->getAllForOwner($userId);
            return $queries;
        };

        // Warm any lazy cache first, so the comparison measures the reads only.
        expect(ownerReadsApi()->getAllForOwner($small->id)->totalCount)->toBe(5);
        expect(ownerReadsApi()->getAllForOwner($large->id)->totalCount)->toBe(20);

        $smallCount = $count($small->id);
        $largeCount = $count($large->id);

        expect($largeCount)->toBe($smallCount);
    });
});

describe('QuotePublicApi::getOwnedQuote', function () {
    it('returns the quote for its owner', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id, ['highlighted_text' => 'passage']);

        $dto = ownerReadsApi()->getOwnedQuote($quote->id, $reader->id);

        expect($dto)->toBeInstanceOf(QuoteDto::class);
        expect($dto->id)->toBe($quote->id);
        expect($dto->highlightedText)->toBe('passage');
        expect($dto->storyId)->toBe($story->id);
        expect($dto->chapterId)->toBe($chapter->id);
        expect($dto->storyTitle)->toBe('Story');
    });

    it('returns null for another user, without throwing', function () {
        $author = alice($this);
        $reader = bob($this);
        $other = carol($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);

        expect(ownerReadsApi()->getOwnedQuote($quote->id, $other->id))->toBeNull();
    });

    it('returns null for an unknown id', function () {
        $reader = bob($this);

        expect(ownerReadsApi()->getOwnedQuote(999999, $reader->id))->toBeNull();
    });

    it('returns null for a soft-deleted quote', function () {
        $author = alice($this);
        $reader = bob($this);
        $story = publicStory('Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author);
        $quote = createQuote($reader->id, $chapter->id, $story->id);
        $quote->delete();

        expect(ownerReadsApi()->getOwnedQuote($quote->id, $reader->id))->toBeNull();
    });
});
