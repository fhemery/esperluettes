<?php

use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use App\Domains\Story\Public\Events\DTO\StorySnapshot;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\StoryDeleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    resetStatistics();
});

function statisticsStorySnapshot(int $storyId = 1, int $userId = 1): StorySnapshot
{
    return new StorySnapshot(
        storyId: $storyId,
        createdByUserId: $userId,
        title: 'Test Story',
        slug: 'test-story-' . $storyId,
        visibility: 'public',
        summaryWordCount: 10,
        summaryCharCount: 50,
        typeId: 1,
        audienceId: 1,
        copyrightId: 1,
        statusId: null,
        feedbackId: null,
        genreIds: [],
        triggerWarningIds: [],
    );
}

function statisticsChapterSnapshot(int $id = 1, int $wordCount = 100): ChapterSnapshot
{
    return new ChapterSnapshot(
        id: $id,
        title: 'Chapter ' . $id,
        slug: 'chapter-' . $id,
        sortOrder: 100,
        status: 'published',
        wordCount: $wordCount,
        charCount: $wordCount * 5,
    );
}

describe('Story content statistics - event-driven updates', function () {
    it('increments story counts when StoryCreated is emitted', function () {
        dispatchEvent(new StoryCreated(statisticsStorySnapshot(storyId: 1, userId: 42)));

        expect(getStatisticValue('global.total_stories'))->toBe(1.0)
            ->and(getStatisticValue('user.total_stories', 'user', 42))->toBe(1.0)
            ->and(getStatisticValue('user.total_stories', 'user', 99))->toBeNull();
    });

    it('decrements story counts when StoryDeleted is emitted', function () {
        $author = alice($this);
        $story = createStoryForAuthor($author->id);
        $chapter = statisticsChapterSnapshot(id: 10, wordCount: 250);

        dispatchEvent(new StoryCreated(statisticsStorySnapshot(storyId: (int) $story->id, userId: $author->id)));
        dispatchEvent(new ChapterCreated(storyId: (int) $story->id, chapter: $chapter));
        dispatchEvent(new StoryDeleted(statisticsStorySnapshot(storyId: (int) $story->id, userId: $author->id), [$chapter]));

        expect(getStatisticValue('global.total_stories'))->toBe(0.0)
            ->and(getStatisticValue('global.total_chapters'))->toBe(0.0)
            ->and(getStatisticValue('global.total_words'))->toBe(0.0);
    });

    it('increments chapter and word counts when ChapterCreated is emitted', function () {
        $author = alice($this);
        $story = createStoryForAuthor($author->id);

        dispatchEvent(new ChapterCreated(
            storyId: (int) $story->id,
            chapter: statisticsChapterSnapshot(id: 5, wordCount: 120),
        ));

        expect(getStatisticValue('global.total_chapters'))->toBe(1.0)
            ->and(getStatisticValue('global.total_words'))->toBe(120.0)
            ->and(getStatisticValue('user.total_chapters', 'user', $author->id))->toBe(1.0)
            ->and(getStatisticValue('user.total_words', 'user', $author->id))->toBe(120.0);
    });

    it('adjusts word counts when ChapterUpdated is emitted', function () {
        $author = alice($this);
        $story = createStoryForAuthor($author->id);

        dispatchEvent(new ChapterCreated(storyId: (int) $story->id, chapter: statisticsChapterSnapshot(id: 5, wordCount: 100)));
        dispatchEvent(new ChapterUpdated(
            storyId: (int) $story->id,
            before: statisticsChapterSnapshot(id: 5, wordCount: 100),
            after: statisticsChapterSnapshot(id: 5, wordCount: 180),
        ));

        expect(getStatisticValue('global.total_words'))->toBe(180.0)
            ->and(getStatisticValue('user.total_words', 'user', $author->id))->toBe(180.0);
    });

    it('decrements chapter and word counts when ChapterDeleted is emitted', function () {
        $author = alice($this);
        $story = createStoryForAuthor($author->id);
        $chapter = statisticsChapterSnapshot(id: 5, wordCount: 90);

        dispatchEvent(new ChapterCreated(storyId: (int) $story->id, chapter: $chapter));
        dispatchEvent(new ChapterDeleted(storyId: (int) $story->id, chapter: $chapter));

        expect(getStatisticValue('global.total_chapters'))->toBe(0.0)
            ->and(getStatisticValue('global.total_words'))->toBe(0.0);
    });
});
