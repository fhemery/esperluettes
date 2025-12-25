<?php

namespace App\Domains\Story\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class CoAuthorChapterUpdatedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $userName,
        public readonly string $userSlug,
        public readonly string $storyTitle,
        public readonly string $storySlug,
        public readonly string $chapterTitle,
        public readonly string $chapterSlug,
    ) {}

    public static function type(): string
    {
        return 'story.coauthor.chapter.updated';
    }

    public function toData(): array
    {
        return [
            'user_name' => $this->userName,
            'user_slug' => $this->userSlug,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
            'chapter_title' => $this->chapterTitle,
            'chapter_slug' => $this->chapterSlug,
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
            chapterSlug: (string) ($data['chapter_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $userUrl = $this->userSlug !== '' ? route('profile.show', ['profile' => $this->userSlug]) : '';
        $storyUrl = $this->storySlug !== '' ? route('stories.show', ['slug' => $this->storySlug]) : '';
        $chapterUrl = ($this->storySlug !== '' && $this->chapterSlug !== '')
            ? route('chapters.show', ['storySlug' => $this->storySlug, 'chapterSlug' => $this->chapterSlug])
            : '';

        return __('story::notification.chapter.updated', [
            'user_name' => $this->userName,
            'user_url' => $userUrl,
            'chapter_name' => $this->chapterTitle,
            'chapter_url' => $chapterUrl,
            'story_name' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
