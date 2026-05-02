<?php

namespace App\Domains\Follow\Private\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class NewStoryNotification implements NotificationContent
{
    public function __construct(
        public readonly int $authorId,
        public readonly string $authorName,
        public readonly string $authorSlug,
        public readonly int $storyId,
        public readonly string $storyTitle,
        public readonly string $storySlug,
    ) {}

    public static function type(): string
    {
        return 'follow.new_story';
    }

    public function toData(): array
    {
        return [
            'author_id' => $this->authorId,
            'author_name' => $this->authorName,
            'author_slug' => $this->authorSlug,
            'story_id' => $this->storyId,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            authorId: (int) ($data['author_id'] ?? 0),
            authorName: (string) ($data['author_name'] ?? ''),
            authorSlug: (string) ($data['author_slug'] ?? ''),
            storyId: (int) ($data['story_id'] ?? 0),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $authorUrl = $this->authorSlug !== ''
            ? route('profile.show', ['profile' => $this->authorSlug])
            : '';

        $storyUrl = $this->storySlug !== ''
            ? route('stories.show', ['slug' => $this->storySlug])
            : '';

        return __('follow::notification.new_story.display', [
            'author_name' => $this->authorName,
            'author_url' => $authorUrl,
            'story_title' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
