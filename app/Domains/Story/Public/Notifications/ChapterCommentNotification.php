<?php

namespace App\Domains\Story\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ChapterCommentNotification implements NotificationContent
{
    public function __construct(
        public readonly int $commentId,
        public readonly string $authorName,
        public readonly string $authorSlug,
        public readonly string $chapterTitle,
        public readonly string $storySlug,
        public readonly string $chapterSlug,
        public readonly bool $isReply,
        public readonly ?string $storyName = null,
    ) {}

    public static function type(): string
    {
        return 'story.chapter.comment';
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
            storyName: (string) ($data['story_name'] ?? null) ?: null,
        );
    }

    public function display(): string
    {
        $chapterUrl = route('chapters.show', [
            'storySlug' => $this->storySlug,
            'chapterSlug' => $this->chapterSlug
        ]) . '?comment=' . $this->commentId;

        $authorUrl = $this->authorSlug !== ''
            ? route('profile.show', ['profile' => $this->authorSlug])
            : '';

        // Use story-aware translations if story name is available, otherwise fallback to legacy
        if ($this->storyName) {
            $storyUrl = route('stories.show', ['slug' => $this->storySlug]);
            $key = $this->isReply
                ? 'story::notification.reply_comment.posted_with_story'
                : 'story::notification.root_comment.posted_with_story';

            return __($key, [
                'author_name' => $this->authorName,
                'author_url' => $authorUrl,
                'chapter_name' => $this->chapterTitle,
                'chapter_url_with_comment' => $chapterUrl,
                'story_name' => $this->storyName,
                'story_url' => $storyUrl,
            ]);
        }

        // Legacy fallback for notifications without story name
        $key = $this->isReply
            ? 'story::notification.reply_comment.posted'
            : 'story::notification.root_comment.posted';

        return __($key, [
            'author_name' => $this->authorName,
            'author_url' => $authorUrl,
            'chapter_name' => $this->chapterTitle,
            'chapter_url_with_comment' => $chapterUrl,
        ]);
    }
}
