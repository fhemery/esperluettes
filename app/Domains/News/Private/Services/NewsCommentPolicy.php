<?php

namespace App\Domains\News\Private\Services;

use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use App\Domains\Comment\Public\Api\Contracts\CommentPolicy;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\News\Private\Models\News;
use Illuminate\Validation\ValidationException;

class NewsCommentPolicy implements CommentPolicy
{
    /**
     * The published-only rule, for roots *and* replies.
     *
     * `CommentPublicApi::create()` enforces `canCreateRoot()` on the root path
     * but never calls `canReply()` on the reply path, so this hook — the only
     * one that runs on both — is where a reply to a thread whose article has
     * gone back to draft is refused. Same message the root path produces.
     */
    public function validateCreate(CommentToCreateDto $dto): void
    {
        if (!$this->isPublished($dto->entityId)) {
            throw ValidationException::withMessages(['body' => ['Comment not allowed']]);
        }
    }

    /**
     * Root comments are allowed only on a published article.
     * Returns false (never throws) for an id that does not exist.
     */
    public function canCreateRoot(int $entityId, int $userId): bool
    {
        return $this->isPublished($entityId);
    }

    /**
     * UI only: Comment calls this once per rendered comment to show or hide the
     * reply control, never on the create path. Kept as a constant `true` so a
     * thread does not cost one article lookup per comment — the thread is only
     * reachable on a published article anyway, and `validateCreate()` above is
     * what actually refuses the post.
     */
    public function canReply(CommentDto $parentComment, int $userId): bool
    {
        return true;
    }

    private function isPublished(int $entityId): bool
    {
        $news = News::query()->find($entityId);

        return $news !== null && $news->status === 'published';
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
