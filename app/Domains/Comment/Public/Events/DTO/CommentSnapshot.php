<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Events\DTO;

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Shared\Support\WordCounter;

class CommentSnapshot
{
    public function __construct(
        public readonly int $commentId,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly int $authorId,
        public readonly bool $isReply,
        public readonly ?int $parentCommentId,
        public readonly int $wordCount,
        public readonly int $charCount,
    ) {}

    public static function fromModel(Comment $model): self
    {
        $plain = strip_tags($model->body ?? '');
        $wordCount = WordCounter::count($model->body ?? '');
        $charCount = mb_strlen($plain);
        return new self(
            commentId: (int) $model->id,
            entityType: (string) $model->commentable_type,
            entityId: (int) $model->commentable_id,
            authorId: (int) $model->author_id,
            isReply: $model->parent_comment_id !== null,
            parentCommentId: $model->parent_comment_id !== null ? (int) $model->parent_comment_id : null,
            wordCount: $wordCount,
            charCount: $charCount,
        );
    }

    public function toPayload(): array
    {
        return [
            'comment_id' => $this->commentId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'author_id' => $this->authorId,
            'is_reply' => $this->isReply,
            'parent_comment_id' => $this->parentCommentId,
            'word_count' => $this->wordCount,
            'char_count' => $this->charCount,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            commentId: (int) ($payload['comment_id'] ?? 0),
            entityType: (string) ($payload['entity_type'] ?? ''),
            entityId: (int) ($payload['entity_id'] ?? 0),
            authorId: (int) ($payload['author_id'] ?? 0),
            isReply: (bool) ($payload['is_reply'] ?? false),
            parentCommentId: isset($payload['parent_comment_id']) ? (int) $payload['parent_comment_id'] : null,
            wordCount: (int) ($payload['word_count'] ?? 0),
            charCount: (int) ($payload['char_count'] ?? 0),
        );
    }
}
