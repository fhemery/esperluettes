<?php

namespace App\Domains\Comment\Private\Repositories;

use App\Domains\Comment\Private\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
     * Bulk: determine for each target if there exists at least one root comment that has no
     * replies authored by any user in $authorIds. Returns [entityId => bool].
     */
    public function hasUnrepliedRootsByAuthors(string $entityType, array $entityIds, array $authorIds): array
    {
        if (empty($entityIds) || empty($authorIds)) {
            // If no authors, then every root is unreplied-by-authors; we only care if there exists at least one root.
            // Fall back to: has any root comments at all.
            if (empty($entityIds)) {
                return [];
            }
            $counts = Comment::query()
                ->selectRaw('commentable_id as id, COUNT(*) as cnt')
                ->where('commentable_type', $entityType)
                ->whereNull('parent_comment_id')
                ->whereIn('commentable_id', $entityIds)
                ->groupBy('commentable_id')
                ->get();
            $out = [];
            foreach ($counts as $r) {
                $out[(int)$r->id] = ((int)$r->cnt) > 0;
            }
            return $out;
        }

        // Strategy: left join replies from authors onto roots; roots with no such replies are candidates.
        // Use the query builder to avoid model global scopes interfering with aliases.
        $rows = DB::table('comments as roots')
            ->leftJoin('comments as replies', function ($join) use ($authorIds) {
                $join->on('replies.parent_comment_id', '=', 'roots.id')
                    ->whereIn('replies.author_id', $authorIds);
            })
            ->where('roots.commentable_type', $entityType)
            ->whereNull('roots.parent_comment_id')
            ->whereIn('roots.commentable_id', $entityIds)
            ->groupBy('roots.commentable_id')
            ->selectRaw('roots.commentable_id as id, COUNT(CASE WHEN replies.id IS NULL THEN 1 END) as unmatched_roots')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->id] = ((int)$r->unmatched_roots) > 0;
        }
        return $out;
    }

    /**
     * Efficiently count root comments for a given target without loading items.
     */
    public function countByTarget(string $entityType, array $entityIds, bool $isRoot=false, ?int $authorId = null): array
    {
        $rows = Comment::query()
            ->where('commentable_type', $entityType)
            ->whereIn('commentable_id', $entityIds)
            ->when($isRoot, fn($q) => $q->whereNull('parent_comment_id'))
            ->when($authorId, fn($q) => $q->where('author_id', $authorId))
            ->groupBy('commentable_id')
            ->selectRaw('commentable_id as id, COUNT(*) as cnt')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->id] = (int)$r->cnt;
        }
        return $out;  
    }

    public function countByTargetAndAuthor(string $entityType, int $authorId, bool $isRoot=false): int
    {
        return Comment::query()
            ->where('commentable_type', $entityType)
            ->where('author_id', $authorId)
            ->when($isRoot, fn($q) => $q->whereNull('parent_comment_id'))
            ->groupBy('commentable_id')
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
     * Update the body of a comment and persist changes.
     */
    public function updateBody(int $commentId, string $body): Comment
    {
        $comment = $this->getById($commentId);
        $comment->body = $body;
        $comment->save();
        return $comment;
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

    /**
     * Soft delete all comments (roots and replies) for a given target.
     * Returns affected rows count.
     */
    public function deleteByTarget(string $entityType, int $entityId): int
    {
        return Comment::query()
            ->where('commentable_type', $entityType)
            ->where('commentable_id', $entityId)
            ->forceDelete();
    }
}
