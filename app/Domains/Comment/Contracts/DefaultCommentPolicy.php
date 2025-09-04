<?php

namespace App\Domains\Comment\Contracts;

class DefaultCommentPolicy implements CommentPolicy
{
    public function validateCreate(CommentToCreateDto $dto): void
    {
        // Allow by default
    }

    public function canCreateRoot(int $entityId, int $userId): bool
    {
        return true;
    }

    public function canReply(CommentDto $parentComment, int $userId): bool
    {
        return true;
    }

    public function canEditOwn(CommentDto $comment, int $userId): bool
    {
        return true;
    }

    public function validateEdit(CommentDto $comment, int $userId, string $newBody): void
    {
        // Allow by default
    }

    public function getRootCommentMinLength(): ?int
    {
        return null;
    }

    public function getRootCommentMaxLength(): ?int
    {
        return null;
    }

    public function getReplyCommentMinLength(): ?int
    {
        return null;
    }

    public function getReplyCommentMaxLength(): ?int
    {
        return null;
    }
}
