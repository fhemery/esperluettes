<?php

namespace App\Domains\Story\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ChapterReplyCommentNotification implements NotificationContent
{
    public function __construct(
        public readonly int $commentId,
        public readonly string $authorName,
        public readonly string $authorSlug,
        public readonly string $chapterTitle,
        public readonly string $storySlug,
        public readonly string $chapterSlug,
        public readonly ?string $storyName = null,
    ) {}

    public static function type(): string
    {
        return 'story.chapter.reply_comment';
    }

    public function toData(): array
    {
        return [
            'comment_id'    => $this->commentId,
            'author_name'   => $this->authorName,
            'author_slug'   => $this->authorSlug,
            'chapter_title' => $this->chapterTitle,
            'story_slug'    => $this->storySlug,
            'chapter_slug'  => $this->chapterSlug,
            'story_name'    => $this->storyName,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            commentId:    (int)    ($data['comment_id']    ?? 0),
            authorName:   (string) ($data['author_name']   ?? ''),
            authorSlug:   (string) ($data['author_slug']   ?? ''),
            chapterTitle: (string) ($data['chapter_title'] ?? ''),
            storySlug:    (string) ($data['story_slug']    ?? ''),
            chapterSlug:  (string) ($data['chapter_slug']  ?? ''),
            storyName:    (string) ($data['story_name']    ?? null) ?: null,
        );
    }

    public function display(): string
    {
        $chapterUrl = route('chapters.show', [
            'storySlug'   => $this->storySlug,
            'chapterSlug' => $this->chapterSlug,
        ]) . '?comment=' . $this->commentId;

        $authorUrl = $this->authorSlug !== ''
            ? route('profile.show', ['profile' => $this->authorSlug])
            : '';

        if ($this->storyName) {
            return __(
                'story::notification.reply_comment.posted_with_story',
                [
                    'author_name'             => $this->authorName,
                    'author_url'              => $authorUrl,
                    'chapter_name'            => $this->chapterTitle,
                    'chapter_url_with_comment' => $chapterUrl,
                    'story_name'              => $this->storyName,
                    'story_url'               => route('stories.show', ['slug' => $this->storySlug]),
                ]
            );
        }

        return __(
            'story::notification.reply_comment.posted',
            [
                'author_name'             => $this->authorName,
                'author_url'              => $authorUrl,
                'chapter_name'            => $this->chapterTitle,
                'chapter_url_with_comment' => $chapterUrl,
            ]
        );
    }
}
