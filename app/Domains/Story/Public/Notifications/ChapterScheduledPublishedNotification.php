<?php

namespace App\Domains\Story\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ChapterScheduledPublishedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $storyTitle,
        public readonly string $storySlug,
        public readonly string $chapterTitle,
        public readonly string $chapterSlug,
    ) {}

    public static function type(): string
    {
        return 'story.chapter.scheduled_published';
    }

    public function toData(): array
    {
        return [
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
            'chapter_title' => $this->chapterTitle,
            'chapter_slug' => $this->chapterSlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
            chapterTitle: (string) ($data['chapter_title'] ?? ''),
            chapterSlug: (string) ($data['chapter_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $storyUrl = $this->storySlug !== '' ? route('stories.show', ['slug' => $this->storySlug]) : '';
        $chapterUrl = ($this->storySlug !== '' && $this->chapterSlug !== '')
            ? route('chapters.show', ['storySlug' => $this->storySlug, 'chapterSlug' => $this->chapterSlug])
            : '';

        return __('story::notification.chapter.scheduled_published', [
            'chapter_name' => $this->chapterTitle,
            'chapter_url' => $chapterUrl,
            'story_name' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
