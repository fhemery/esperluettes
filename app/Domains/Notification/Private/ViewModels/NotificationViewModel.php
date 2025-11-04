<?php

namespace App\Domains\Notification\Private\ViewModels;

class NotificationViewModel
{
    public function __construct(
        public int $id,
        public string $contentKey,
        public array $contentData,
        public string $renderedContent,
        public string $createdAt,
        public ?string $readAt = null,
        public ?string $avatarUrl = null,
    ) {}

    /**
     * @param array{id:int,content_key:string,content_data:array|mixed,rendered_content:string,created_at:string,read_at:?string,avatar_url?:?string} $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            contentKey: (string) $row['content_key'],
            contentData: is_array($row['content_data']) ? $row['content_data'] : (array) $row['content_data'],
            renderedContent: (string) $row['rendered_content'],
            createdAt: (string) $row['created_at'],
            readAt: $row['read_at'] ?? null,
            avatarUrl: $row['avatar_url'] ?? null,
        );
    }
}
