<?php

namespace App\Domains\ReadList\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ReadListChapterUnpublishedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $authorName,
        public readonly string $authorSlug,
        public readonly string $storyTitle,
        public readonly string $storySlug,
        public readonly string $chapterTitle,
        public readonly string $chapterSlug,
    ) {}

    public static function type(): string
    {
        return 'readlist.chapter.unpublished';
    }

    public function toData(): array
    {
        return [
            'author_name' => $this->authorName,
            'author_slug' => $this->authorSlug,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
            'chapter_title' => $this->chapterTitle,
            'chapter_slug' => $this->chapterSlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            authorName: (string) ($data['author_name'] ?? ''),
            authorSlug: (string) ($data['author_slug'] ?? ''),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
            chapterTitle: (string) ($data['chapter_title'] ?? ''),
            chapterSlug: (string) ($data['chapter_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $authorUrl = $this->authorSlug !== '' ? route('profile.show', ['profile' => $this->authorSlug]) : '';
        $storyUrl = $this->storySlug !== '' ? route('stories.show', ['slug' => $this->storySlug]) : '';
        $chapterUrl = ($this->storySlug !== '' && $this->chapterSlug !== '')
            ? route('chapters.show', ['storySlug' => $this->storySlug, 'chapterSlug' => $this->chapterSlug])
            : '';

        return __('readlist::notification.chapter_unpublished', [
            'author_name' => $this->authorName,
            'author_url' => $authorUrl,
            'chapter_name' => $this->chapterTitle,
            'chapter_url' => $chapterUrl,
            'story_name' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
