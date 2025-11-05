<?php

namespace App\Domains\ReadList\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ReadListStoryRepublishedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $authorName,
        public readonly string $authorSlug,
        public readonly string $storyTitle,
        public readonly string $storySlug,
    ) {}

    public static function type(): string
    {
        return 'readlist.story.republished';
    }

    public function toData(): array
    {
        return [
            'author_name' => $this->authorName,
            'author_slug' => $this->authorSlug,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            authorName: (string) ($data['author_name'] ?? ''),
            authorSlug: (string) ($data['author_slug'] ?? ''),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $authorUrl = $this->authorSlug !== '' ? route('profile.show', ['profile' => $this->authorSlug]) : '';
        $storyUrl = $this->storySlug !== '' ? route('stories.show', ['slug' => $this->storySlug]) : '';
        return __('readlist::notification.story_republished', [
            'author_name' => $this->authorName,
            'author_url' => $authorUrl,
            'story_name' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
