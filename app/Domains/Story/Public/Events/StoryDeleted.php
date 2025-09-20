<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;
use App\Domains\Story\Public\Events\DTO\StorySnapshot;
use App\Domains\Events\Contracts\DomainEvent;

class StoryDeleted implements DomainEvent
{
    /** @param ChapterSnapshot[] $chapters */
    public function __construct(
        public readonly StorySnapshot $story,
        public readonly array $chapters = [],
    ) {}

    public static function name(): string { return 'Story.Deleted'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'story' => $this->story->toPayload(),
            'chapters' => array_map(fn(ChapterSnapshot $c) => $c->toPayload(), $this->chapters),
        ];
    }

    public function summary(): string
    {
        return trans('story::events.story_deleted.summary', [
            'id' => $this->story->storyId,
            'title' => $this->story->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        $storyPayload = (array) ($payload['story'] ?? []);
        $chaptersPayload = (array) ($payload['chapters'] ?? []);
        $chapters = array_map(fn($p) => ChapterSnapshot::fromPayload((array) $p), $chaptersPayload);
        return new static(StorySnapshot::fromPayload($storyPayload), $chapters);
    }
}
