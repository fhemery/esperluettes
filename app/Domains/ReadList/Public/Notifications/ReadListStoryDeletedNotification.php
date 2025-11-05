<?php

namespace App\Domains\ReadList\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ReadListStoryDeletedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $authorName,
        public readonly string $authorSlug,
        public readonly string $storyTitle,
    ) {}

    public static function type(): string
    {
        return 'readlist.story.deleted';
    }

    public function toData(): array
    {
        return [
            'author_name' => $this->authorName,
            'author_slug' => $this->authorSlug,
            'story_title' => $this->storyTitle,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            authorName: (string) ($data['author_name'] ?? ''),
            authorSlug: (string) ($data['author_slug'] ?? ''),
            storyTitle: (string) ($data['story_title'] ?? ''),
        );
    }

    public function display(): string
    {
        $authorUrl = $this->authorSlug !== '' ? route('profile.show', ['profile' => $this->authorSlug]) : '';
        return __('readlist::notification.story_deleted', [
            'author_name' => $this->authorName,
            'author_url' => $authorUrl,
            'story_name' => $this->storyTitle,
        ]);
    }
}
