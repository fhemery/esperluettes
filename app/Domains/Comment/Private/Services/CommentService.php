<?php
declare(strict_types=1);

namespace App\Domains\Comment\Private\Services;

use App\Domains\Comment\Private\Repositories\CommentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Support\CommentBodySanitizer;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\DTO\CommentSnapshot;
use App\Domains\Comment\Public\Events\CommentEdited;
use App\Domains\Comment\Public\Events\CommentDeletedByModeration;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function __construct(
        private readonly CommentRepository $repository,
        private readonly CommentBodySanitizer $sanitizer,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * Retrieve comments for a given entity type and id as domain models.
     */
    public function getFor(string $entityType, int $entityId, int $page = 1, int $perPage = 20, bool $withChildren = false): LengthAwarePaginator
    {
        // Assume caller passes normalized identifiers already.
        return $this->repository->listByTarget($entityType, $entityId, $page, $perPage, $withChildren);
    }

    /**
     * Count total root comments for a given target.
     */
    public function countFor(string $entityType, array $entityIds, bool $isRoot=false, ?int $authorId = null): array
    {
        return $this->repository->countByTarget($entityType, $entityIds, $isRoot, $authorId);
    }

    /**
     * Count total root comments for a given target.
     */
    public function countForAuthor(string $entityType,int $authorId, bool $isRoot=false, ): int
    {
        return $this->repository->countByTargetAndAuthor($entityType, $authorId, $isRoot);
    }

    /**
     * For each target, determines if there exists at least one root comment without a reply
     * from any of the provided $authorIds.
     * @return array<int,bool> [entityId => hasUnreplied]
     */
    public function hasUnrepliedRootsByAuthors(string $entityType, array $entityIds, array $authorIds): array
    {
        return $this->repository->hasUnrepliedRootsByAuthors($entityType, $entityIds, $authorIds);
    }

    /**
     * Create a root comment (no parent). No policy checks for now.
     */
    public function postComment(string $entityType, int $entityId, int $authorId, string $body, ?int $parentCommentId = null): Comment
    {
        $cleanBody = $this->sanitizeBody($body);
        $comment = $this->repository->create($entityType, $entityId, $authorId, $cleanBody, $parentCommentId);
        
        $snapshot = CommentSnapshot::fromModel($comment);
        $this->eventBus->emit(new CommentPosted($snapshot));
        
        return $comment;
    }

    /**
     * Retrieve a comment by id as domain model.
     */
    public function getComment(int $commentId): Comment
    {
        return $this->repository->getById($commentId);
    }

    /**
     * Check whether the given user already posted a root comment on the specified target.
     */
    public function userHasRoot(string $entityType, int $entityId, int $userId): bool
    {
        return $this->repository->userHasRoot($entityType, $entityId, $userId);
    }

    /**
     * Sanitize using configured HTML Purifier with the 'strict' profile.
     */
    private function sanitizeBody(string $body): string
    {
        return $this->sanitizer->sanitizeToHtml($body);
    }

    /**
     * Update a comment body after sanitization.
     */
    public function updateComment(int $commentId, string $newBody): Comment
    {
        // Build BEFORE snapshot
        $existing = $this->repository->getById($commentId);
        $before = CommentSnapshot::fromModel($existing);

        // Update
        $cleanBody = $this->sanitizeBody($newBody);
        $updated = $this->repository->updateBody($commentId, $cleanBody);

        // Build AFTER snapshot and emit event
        $after = CommentSnapshot::fromModel($updated);
        $this->eventBus->emit(new CommentEdited($before, $after));

        return $updated;
    }

    /**
     * Nullify author_id for all comments authored by the given user.
     * Returns affected rows count.
     */
    public function nullifyAuthor(int $userId): int
    {
        return $this->repository->nullifyAuthor($userId);
    }

    /**
     * Moderation: delete a comment and its direct children.
     */
    public function deleteByModeration(int $commentId): void
    {
        DB::transaction(function () use ($commentId) {
            $comment = $this->repository->getById($commentId);
            $this->repository->deleteWithChildren($commentId);

            $this->eventBus->emit(new CommentDeletedByModeration(
                commentId: (int)$commentId,
                entityType: (string)$comment->commentable_type,
                entityId: (int)$comment->commentable_id,
                isRoot: $comment->parent_comment_id === null,
                authorId: $comment->author_id !== null ? (int)$comment->author_id : null,
            ));
        });
    }
}
