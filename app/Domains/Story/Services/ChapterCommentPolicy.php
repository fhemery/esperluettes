<?php

namespace App\Domains\Story\Services;

use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Contracts\CommentPolicy;
use App\Domains\Comment\Contracts\CommentToCreateDto;

class ChapterCommentPolicy implements CommentPolicy
{
    public function __construct(private readonly ChapterService $chapters, private readonly StoryService $stories)
    {
    }

    public function validateCreate(CommentToCreateDto $dto): void
    {
        return;
    }

    public function canCreateRoot(int $entityId, int $userId): bool
    {
        $isAuthor = $this->chapters->isUserAuthorOfChapter($entityId, $userId);
        // If chapter not found, default to allow (policy should not 404)
        return $isAuthor ? false : true;
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
        return;
    }

    public function getMinBodyLength(): ?int
    {
        return 140;
    }

    public function getMaxBodyLength(): ?int
    {
        return null;
    }
}
