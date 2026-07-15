<?php

namespace App\Domains\Statistics\Private\Support;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\StoryDeleted;

final class StoryContentDeltaCalculator
{
    public function __construct(
        private readonly StoryPublicApi $storyApi,
    ) {}

    /**
     * @return array<mixed, float|int>|null
     */
    public function forStories(DomainEvent $event, string $scopeType): ?array
    {
        if ($event instanceof StoryCreated) {
            return $this->singleUserScopeMap($scopeType, $event->story->createdByUserId, 1);
        }

        if ($event instanceof StoryDeleted) {
            return $this->singleUserScopeMap($scopeType, $event->story->createdByUserId, -1);
        }

        return null;
    }

    /**
     * @return array<mixed, float|int>|null
     */
    public function forChapters(DomainEvent $event, string $scopeType): ?array
    {
        if ($event instanceof ChapterCreated) {
            return $this->authorScopeMap($scopeType, $event->storyId, 1);
        }

        if ($event instanceof ChapterDeleted) {
            return $this->authorScopeMap($scopeType, $event->storyId, -1);
        }

        if ($event instanceof StoryDeleted) {
            $delta = -count($event->chapters);

            if ($delta === 0) {
                return null;
            }

            return $this->authorScopeMap($scopeType, $event->story->storyId, $delta);
        }

        return null;
    }

    /**
     * @return array<mixed, float|int>|null
     */
    public function forWords(DomainEvent $event, string $scopeType): ?array
    {
        if ($event instanceof ChapterCreated) {
            return $this->authorScopeMap($scopeType, $event->storyId, $event->chapter->wordCount);
        }

        if ($event instanceof ChapterDeleted) {
            return $this->authorScopeMap($scopeType, $event->storyId, -$event->chapter->wordCount);
        }

        if ($event instanceof ChapterUpdated) {
            $delta = $event->after->wordCount - $event->before->wordCount;

            if ($delta === 0) {
                return null;
            }

            return $this->authorScopeMap($scopeType, $event->storyId, $delta);
        }

        if ($event instanceof StoryDeleted) {
            $delta = -array_sum(array_map(
                fn ($chapter) => $chapter->wordCount,
                $event->chapters,
            ));

            if ($delta === 0) {
                return null;
            }

            return $this->authorScopeMap($scopeType, $event->story->storyId, $delta);
        }

        return null;
    }

    /**
     * @return array<mixed, float|int>
     */
    private function singleUserScopeMap(string $scopeType, int $userId, int|float $delta): array
    {
        if ($scopeType === 'global') {
            return [null => $delta];
        }

        return [$userId => $delta];
    }

    /**
     * @return array<mixed, float|int>
     */
    private function authorScopeMap(string $scopeType, int $storyId, int|float $delta): array
    {
        if ($scopeType === 'global') {
            return [null => $delta];
        }

        $authorIds = $this->storyApi->getAuthorIds($storyId);

        if ($authorIds === []) {
            return [];
        }

        $result = [];

        foreach ($authorIds as $authorId) {
            $result[$authorId] = $delta;
        }

        return $result;
    }
}
