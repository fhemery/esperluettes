<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Api;

use App\Domains\Comment\Private\Repositories\CommentRepository;

class CommentMaintenancePublicApi
{
    public function __construct(
        private readonly CommentRepository $repository,
    ) {}

    /**
     * Delete all comments (roots and replies) for a given target.
     * Returns the number of affected rows (soft-deleted per model's SoftDeletes).
     */
    public function deleteFor(string $entityType, int $entityId): int
    {
        return $this->repository->deleteByTarget($entityType, $entityId);
    }
}
