<?php

namespace App\Domains\Discord\Tests\Fixtures;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * Notification content shaped like a real domain notification: several fields,
 * mixed scalar types (int, string, bool) and a nullable field.
 *
 * Crucially, no key here aliases what display() renders — so a test asserting on
 * the API payload cannot pass by accident if the controller derives the payload
 * from the rendered text instead of returning the stored data verbatim.
 */
class ComplexTestNotificationContent implements NotificationContent
{
    public function __construct(
        public readonly int $commentId = 42,
        public readonly string $authorName = 'Daniel',
        public readonly string $authorSlug = 'daniel',
        public readonly string $chapterTitle = 'Chapitre 2.1',
        public readonly string $storySlug = 'immortelle-le-roman',
        public readonly string $chapterSlug = 'chapitre-21-7',
        public readonly bool $isReply = true,
        public readonly ?string $storyName = 'Immortelle, le roman',
    ) {}

    public static function type(): string
    {
        return 'discord.test.complex_notification';
    }

    public function toData(): array
    {
        return [
            'comment_id' => $this->commentId,
            'author_name' => $this->authorName,
            'author_slug' => $this->authorSlug,
            'chapter_title' => $this->chapterTitle,
            'story_slug' => $this->storySlug,
            'chapter_slug' => $this->chapterSlug,
            'is_reply' => $this->isReply,
            'story_name' => $this->storyName,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            commentId: (int) ($data['comment_id'] ?? 0),
            authorName: (string) ($data['author_name'] ?? ''),
            authorSlug: (string) ($data['author_slug'] ?? ''),
            chapterTitle: (string) ($data['chapter_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
            chapterSlug: (string) ($data['chapter_slug'] ?? ''),
            isReply: (bool) ($data['is_reply'] ?? false),
            storyName: isset($data['story_name']) ? (string) $data['story_name'] : null,
        );
    }

    public function display(): string
    {
        return '<a href="/profile/' . $this->authorSlug . '">' . $this->authorName . '</a>'
            . ' a commenté <a href="/stories/' . $this->storySlug . '/chapters/' . $this->chapterSlug . '">'
            . $this->chapterTitle . '</a>';
    }
}
