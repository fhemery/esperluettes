<?php

namespace App\Domains\Comment\Public\Api\Contracts;

use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Comment\Private\Models\Comment;

class CommentDto
{
    /**
     * @var CommentDto[] $children
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly int $id,
        public readonly string $body,
        public readonly ?int $authorId,
        public readonly ProfileDto $authorProfile,
        public readonly string $createdAt,
        public readonly ?string $updatedAt = null,
        public bool $canReply = true,
        public bool $canEditOwn = false,
        public readonly array $children = [],
    ) {
    }

    public static function fromModel(Comment $model, ProfileDto $authorProfile, array $children = [], bool $canReply = true, bool $canEditOwn = false): self
    {
        return new self(
            entityType: (string) $model->commentable_type,
            entityId: (int) $model->commentable_id,
            id: (int) $model->id,
            body: (string) $model->body,
            authorId: $model->author_id !== null ? (int) $model->author_id : null,
            authorProfile: $authorProfile,
            createdAt: $model->created_at?->toISOString() ?? '',
            updatedAt: $model->updated_at?->toISOString(),
            canReply: $canReply,
            canEditOwn: $canEditOwn,
            children: $children,
        );
    }

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'id' => $this->id,
            'body' => $this->body,
            'author_id' => $this->authorId,
            'author_profile' => [
                'user_id' => $this->authorProfile->user_id,
                'display_name' => $this->authorProfile->display_name,
                'slug' => $this->authorProfile->slug,
                'avatar_url' => $this->authorProfile->avatar_url,
            ],
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'can_reply' => $this->canReply,
            'can_edit_own' => $this->canEditOwn,
            'children' => array_map(fn(self $c) => $c->toArray(), $this->children),
        ];
    }
}
