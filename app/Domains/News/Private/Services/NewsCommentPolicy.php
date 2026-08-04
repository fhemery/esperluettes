<?php

namespace App\Domains\News\Private\Services;

use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use App\Domains\Comment\Public\Api\Contracts\CommentPolicy;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\News\Private\Models\News;

class NewsCommentPolicy implements CommentPolicy
{
    public function validateCreate(CommentToCreateDto $dto): void
    {
        return;
    }

    /**
     * Root comments are allowed only on a published article.
     * Returns false (never throws) for an id that does not exist.
     */
    public function canCreateRoot(int $entityId, int $userId): bool
    {
        $news = News::query()->find($entityId);

        return $news !== null && $news->status === 'published';
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
        return 20;
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

    public function getUrl(int $entityId, int $commentId): ?string
    {
        $news = News::query()->find($entityId);
        if (!$news) {
            return null;
        }

        return route('news.show', ['slug' => $news->slug]) . '?comment=' . $commentId;
    }
}
