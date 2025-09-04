<?php

namespace App\Domains\Comment\Services;

use App\Domains\Comment\Repositories\CommentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Domains\Comment\Models\Comment;
use Mews\Purifier\Facades\Purifier;

class CommentService
{
    public function __construct(
        private readonly CommentRepository $repository,
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
    public function countFor(string $entityType, int $entityId): int
    {
        return $this->repository->countByTarget($entityType, $entityId);
    }

    /**
     * Create a root comment (no parent). No policy checks for now.
     */
    public function postComment(string $entityType, int $entityId, int $authorId, string $body, ?int $parentCommentId = null): Comment
    {
        $cleanBody = $this->sanitizeBody($body);
        return $this->repository->create($entityType, $entityId, $authorId, $cleanBody, $parentCommentId);
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
        $clean = Purifier::clean($body, 'strict');
        return is_string($clean) ? trim($clean) : '';
    }
}
