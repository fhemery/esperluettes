<?php

namespace App\Domains\ReadList\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ReadListAddedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $readerName,
        public readonly string $readerSlug,
        public readonly string $storyTitle,
        public readonly string $storySlug,
    ) {}

    public static function type(): string
    {
        return 'readlist.story.added';
    }

    public function toData(): array
    {
        return [
            'reader_name' => $this->readerName,
            'reader_slug' => $this->readerSlug,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            readerName: (string) ($data['reader_name'] ?? ''),
            readerSlug: (string) ($data['reader_slug'] ?? ''),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $readerUrl = $this->readerSlug !== ''
            ? route('profile.show', ['profile' => $this->readerSlug])
            : '';
        $storyUrl = $this->storySlug !== ''
            ? route('stories.show', ['slug' => $this->storySlug])
            : '';

        return __('readlist::notification.story_added', [
            'reader_name' => $this->readerName,
            'reader_url' => $readerUrl,
            'story_name' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
