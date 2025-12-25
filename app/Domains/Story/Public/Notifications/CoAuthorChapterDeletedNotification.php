<?php

namespace App\Domains\Story\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class CoAuthorChapterDeletedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $userName,
        public readonly string $userSlug,
        public readonly string $storyTitle,
        public readonly string $storySlug,
        public readonly string $chapterTitle,
    ) {}

    public static function type(): string
    {
        return 'story.coauthor.chapter.deleted';
    }

    public function toData(): array
    {
        return [
            'user_name' => $this->userName,
            'user_slug' => $this->userSlug,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
            'chapter_title' => $this->chapterTitle,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            userName: (string) ($data['user_name'] ?? ''),
            userSlug: (string) ($data['user_slug'] ?? ''),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
            chapterTitle: (string) ($data['chapter_title'] ?? ''),
        );
    }

    public function display(): string
    {
        $userUrl = $this->userSlug !== '' ? route('profile.show', ['profile' => $this->userSlug]) : '';
        $storyUrl = $this->storySlug !== '' ? route('stories.show', ['slug' => $this->storySlug]) : '';

        return __('story::notification.chapter.deleted', [
            'user_name' => $this->userName,
            'user_url' => $userUrl,
            'chapter_name' => $this->chapterTitle,
            'story_name' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
