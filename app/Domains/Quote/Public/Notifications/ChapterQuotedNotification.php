<?php

namespace App\Domains\Quote\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ChapterQuotedNotification implements NotificationContent
{
    public function __construct(
        public readonly int $quoterId,
        public readonly string $quoterName,
        public readonly string $quoterSlug,
        public readonly int $chapterId,
        public readonly string $chapterTitle,
        public readonly string $chapterSlug,
        public readonly int $storyId,
        public readonly string $storyTitle,
        public readonly string $storySlug,
    ) {
    }

    public static function type(): string
    {
        return 'quote.chapter_quoted';
    }

    public function toData(): array
    {
        return [
            'quoter_id' => $this->quoterId,
            'quoter_name' => $this->quoterName,
            'quoter_slug' => $this->quoterSlug,
            'chapter_id' => $this->chapterId,
            'chapter_title' => $this->chapterTitle,
            'chapter_slug' => $this->chapterSlug,
            'story_id' => $this->storyId,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            quoterId: (int) ($data['quoter_id'] ?? 0),
            quoterName: (string) ($data['quoter_name'] ?? ''),
            quoterSlug: (string) ($data['quoter_slug'] ?? ''),
            chapterId: (int) ($data['chapter_id'] ?? 0),
            chapterTitle: (string) ($data['chapter_title'] ?? ''),
            chapterSlug: (string) ($data['chapter_slug'] ?? ''),
            storyId: (int) ($data['story_id'] ?? 0),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $quoterUrl = $this->quoterSlug !== ''
            ? route('profile.show', ['profile' => $this->quoterSlug])
            : '';

        $chapterUrl = ($this->storySlug !== '' && $this->chapterSlug !== '')
            ? route('chapters.show', ['storySlug' => $this->storySlug, 'chapterSlug' => $this->chapterSlug])
            : '';

        return __('quote::notification.chapter_quoted.display', [
            'quoter_name' => $this->quoterName,
            'quoter_url' => $quoterUrl,
            'chapter_title' => $this->chapterTitle,
            'chapter_url' => $chapterUrl,
            'story_title' => $this->storyTitle,
        ]);
    }
}
