<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Api\Contracts;

use App\Domains\Shared\Dto\ProfileDto;

class AnnotationDto
{
    /**
     * @param AnnotationDto[] $replies Same shape as roots; always empty in v1
     */
    public function __construct(
        public readonly int $id,
        public readonly int $commentId,
        public readonly ?int $parentAnnotationId,
        public readonly ?int $authorId,
        public readonly ProfileDto $authorProfile,
        public readonly string $body,
        public readonly string $highlightedText,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
        public readonly ?bool $isProcessed,
        public readonly string $createdAt,
        public readonly array $replies,
        public readonly bool $canMarkAsProcessed,
        public readonly bool $canDelete,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'comment_id' => $this->commentId,
            'parent_annotation_id' => $this->parentAnnotationId,
            'author_id' => $this->authorId,
            'author_profile' => [
                'user_id' => $this->authorProfile->user_id,
                'display_name' => $this->authorProfile->display_name,
                'slug' => $this->authorProfile->slug,
                'avatar_url' => $this->authorProfile->avatar_url,
            ],
            'body' => $this->body,
            'highlighted_text' => $this->highlightedText,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'is_processed' => $this->isProcessed,
            'created_at' => $this->createdAt,
            'replies' => array_map(fn (self $r) => $r->toArray(), $this->replies),
            'can_mark_as_processed' => $this->canMarkAsProcessed,
            'can_delete' => $this->canDelete,
        ];
    }
}
