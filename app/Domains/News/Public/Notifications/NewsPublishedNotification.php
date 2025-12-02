<?php

namespace App\Domains\News\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

final class NewsPublishedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $newsTitle,
        public readonly string $newsSlug,
    ) {}

    public static function type(): string
    {
        return 'news.published';
    }

    public function toData(): array
    {
        return [
            'news_title' => $this->newsTitle,
            'news_slug' => $this->newsSlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            newsTitle: (string) ($data['news_title'] ?? ''),
            newsSlug: (string) ($data['news_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $newsUrl = $this->newsSlug !== ''
            ? route('news.show', ['slug' => $this->newsSlug])
            : '';

        return __('news::notification.published', [
            'news_title' => e($this->newsTitle),
            'news_url' => $newsUrl,
        ]);
    }
}
