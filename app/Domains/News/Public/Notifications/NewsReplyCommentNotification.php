<?php

namespace App\Domains\News\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class NewsReplyCommentNotification implements NotificationContent
{
    public function __construct(
        public readonly int $commentId,
        public readonly string $authorName,
        public readonly string $authorSlug,
        public readonly string $newsTitle,
        public readonly string $newsSlug,
    ) {}

    public static function type(): string
    {
        return 'news.reply_comment';
    }

    public function toData(): array
    {
        return [
            'comment_id'  => $this->commentId,
            'author_name' => $this->authorName,
            'author_slug' => $this->authorSlug,
            'news_title'  => $this->newsTitle,
            'news_slug'   => $this->newsSlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            commentId:   (int)    ($data['comment_id']  ?? 0),
            authorName:  (string) ($data['author_name'] ?? ''),
            authorSlug:  (string) ($data['author_slug'] ?? ''),
            newsTitle:   (string) ($data['news_title']  ?? ''),
            newsSlug:    (string) ($data['news_slug']   ?? ''),
        );
    }

    public function display(): string
    {
        $newsUrl = route('news.show', ['slug' => $this->newsSlug]) . '?comment=' . $this->commentId;

        $authorUrl = $this->authorSlug !== ''
            ? route('profile.show', ['profile' => $this->authorSlug])
            : '';

        return __('news::notification.reply_comment.posted', [
            'author_name'           => $this->authorName,
            'author_url'            => $authorUrl,
            'news_title'            => $this->newsTitle,
            'news_url_with_comment' => $newsUrl,
        ]);
    }
}
