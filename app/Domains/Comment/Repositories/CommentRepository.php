<?php

namespace App\Domains\Comment\Repositories;

use App\Domains\Comment\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentRepository
{
    public function listByTarget(string $entityType, int $entityId, int $page = 1, int $perPage = 20, bool $withChildren = false): LengthAwarePaginator
    {
        $query = Comment::query()
            ->where('commentable_type', $entityType)
            ->where('commentable_id', $entityId)
            ->whereNull('parent_comment_id')
            ->orderByDesc('created_at');

        if ($withChildren) {
            $query->with(['children' => function($q) {
                $q->orderBy('created_at', 'asc');
            }]);
        }

        return $query->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Efficiently count root comments for a given target without loading items.
     */
    public function countByTarget(string $entityType, int $entityId): int
    {
        return Comment::query()
            ->where('commentable_type', $entityType)
            ->where('commentable_id', $entityId)
            ->whereNull('parent_comment_id')
            ->count();
    }

    public function create(string $entityType, int $entityId, int $authorId, string $body, ?int $parentCommentId = null): Comment
    {
        return Comment::query()->create([
            'commentable_type' => $entityType,
            'commentable_id'   => $entityId,
            'author_id'        => $authorId,
            'body'             => $body,
            'parent_comment_id' => $parentCommentId,
        ]);
    }

    public function getById(int $commentId): Comment
    {
        return Comment::query()->findOrFail($commentId);
    }

    /**
     * Check if a given user already has a root comment on the specified target.
     */
    public function userHasRoot(string $entityType, int $entityId, int $userId): bool
    {
        return Comment::query()
            ->where('commentable_type', $entityType)
            ->where('commentable_id', $entityId)
            ->whereNull('parent_comment_id')
            ->where('author_id', $userId)
            ->exists();
    }
}
