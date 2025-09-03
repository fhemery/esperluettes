<?php

namespace App\Domains\Comment\Contracts;

class DefaultCommentPolicy implements CommentPolicy
{
    public function validateCreate(CommentToCreateDto $dto): void
    {
        // Allow by default
    }

    public function canCreateRoot(string $entityType, int $entityId, int $userId): bool
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

    public function getMinBodyLength(): ?int
    {
        return null;
    }

    public function getMaxBodyLength(): ?int
    {
        return null;
    }
}
