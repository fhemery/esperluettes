<?php

namespace App\Domains\Story\Services;

use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Contracts\CommentPolicy;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Comment\PublicApi\CommentPublicApi;

class ChapterCommentPolicy implements CommentPolicy
{
    public function __construct(
        private readonly ChapterService $chapters,
        private readonly StoryService $stories,
        private readonly CommentPublicApi $comments,
    )
    {
    }

    public function validateCreate(CommentToCreateDto $dto): void
    {
        return;
    }

    public function canCreateRoot(int $entityId, int $userId): bool
    {
        // entityId is the Chapter id. Forbid root comments when:
        // - the user is an author/co-author of the chapter's parent story, OR
        // - the user already posted a root comment on this chapter.
        // Otherwise allow.
        $isAuthor = $this->chapters->isUserAuthorOfChapter($entityId, $userId);
        if ($isAuthor) {
            return false;
        }
        $alreadyPosted = $this->comments->userHasRoot('chapter', $entityId, $userId);
        return !$alreadyPosted;
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

    public function getRootCommentMinLength(): ?int
    {
        return 140;
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
