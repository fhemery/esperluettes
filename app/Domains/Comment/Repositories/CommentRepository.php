<?php

namespace App\Domains\Comment\Repositories;

use App\Domains\Comment\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentRepository
{
    public function listByTarget(string $entityType, int $entityId, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Comment::query()
            ->where('commentable_type', $entityType)
            ->where('commentable_id', $entityId)
            ->orderByDesc('created_at')
            ->paginate(perPage: $perPage, page: $page);
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
}
