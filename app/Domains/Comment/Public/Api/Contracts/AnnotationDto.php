<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Api\Contracts;

use App\Domains\Shared\Dto\ProfileDto;

class AnnotationDto
{
    /**
     * @param AnnotationDto[] $replies Oldest first, same shape as roots; always empty on a reply
     * @param bool $canEdit Display hint: the viewer wrote this root (commenter)
     * @param bool $canReply Display hint: the viewer may reply under this root
     * @param bool $canDelete Display hint: moderator, or the writer of this reply
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
        public readonly bool $canEdit,
        public readonly bool $canReply,
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
            'can_edit' => $this->canEdit,
            'can_reply' => $this->canReply,
        ];
    }
}
