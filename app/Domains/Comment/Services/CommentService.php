<?php
declare(strict_types=1);

namespace App\Domains\Comment\Services;

use App\Domains\Comment\Repositories\CommentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Domains\Comment\Models\Comment;
use App\Domains\Comment\Support\CommentBodySanitizer;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Comment\Events\CommentPosted;
use App\Domains\Comment\Events\DTO\CommentSnapshot;
use App\Domains\Comment\Events\CommentEdited;

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
    public function countFor(string $entityType, int $entityId, bool $isRoot=false, ?int $authorId = null): int
    {
        return $this->repository->countByTarget($entityType, $entityId, $isRoot, $authorId);
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
}
