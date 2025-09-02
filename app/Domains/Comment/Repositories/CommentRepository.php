<?php

namespace App\Domains\Comment\Repositories;

use App\Domains\Comment\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentRepository
{
    public function listByTarget(string $entityType, string $entityId, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Comment::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function createRoot(string $entityType, string $entityId, int $authorId, string $body): Comment
    {
        return Comment::query()->create([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'author_id'   => $authorId,
            'body'        => $body,
        ]);
    }
}
