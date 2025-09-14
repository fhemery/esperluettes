<?php

declare(strict_types=1);

namespace App\Domains\Story\Events;

use App\Domains\Events\Contracts\DomainEvent;

class StoryVisibilityChanged implements DomainEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly string $title,
        public readonly string $oldVisibility,
        public readonly string $newVisibility,
    ) {}

    public static function name(): string { return 'Story.VisibilityChanged'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'story_id' => $this->storyId,
            'title' => $this->title,
            'old_visibility' => $this->oldVisibility,
            'new_visibility' => $this->newVisibility,
        ];
    }

    public function summary(): string
    {
        $oldLabel = trans('story::shared.visibility.options.' . $this->oldVisibility);
        $newLabel = trans('story::shared.visibility.options.' . $this->newVisibility);
        return trans('story::events.story_visibility_changed.summary', [
            'id' => $this->storyId,
            'title' => $this->title,
            'old' => $oldLabel,
            'new' => $newLabel,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            storyId: (int) ($payload['story_id'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
            oldVisibility: (string) ($payload['old_visibility'] ?? ''),
            newVisibility: (string) ($payload['new_visibility'] ?? ''),
        );
    }
}
