<?php

namespace App\Domains\Comment\PublicApi;

use App\Domains\Comment\Contracts\CommentDto;
use App\Domains\Comment\Contracts\CommentPolicy;
use App\Domains\Comment\Contracts\CommentToCreateDto;

class CommentPolicyRegistry
{
    /** @var array<string, CommentPolicy> */
    private array $policies = [];

    /**
     * Register a policy for a given entity type.
     */
    public function register(string $entityType, CommentPolicy $policy): void
    {
        $this->policies[$entityType] = $policy;
    }

    /**
     * Validate creation rules if a policy is present.
     */
    public function validateCreate(CommentToCreateDto $dto): void
    {
        $policy = $this->policies[$dto->entityType] ?? null;
        if ($policy instanceof CommentPolicy) {
            $policy->validateCreate($dto);
        }
    }

    /**
     * Whether the user can create a root comment. Default: true when no policy.
     */
    public function canCreateRoot(string $entityType, int $entityId, int $userId): bool
    {
        $policy = $this->policies[$entityType] ?? null;
        if ($policy instanceof CommentPolicy) {
            return $policy->canCreateRoot($entityType, $entityId, $userId);
        }
        return true;
    }

    /**
     * Whether the user can reply to the given parent comment. Default: true.
     */
    public function canReply(string $entityType, CommentDto $parentComment, int $userId): bool
    {
        $policy = $this->policies[$entityType] ?? null;
        if ($policy instanceof CommentPolicy) {
            return $policy->canReply($parentComment, $userId);
        }
        return true;
    }

    /**
     * Whether the user can see the Edit control for their own comment. Default: true.
     */
    public function canEditOwn(string $entityType, CommentDto $comment, int $userId): bool
    {
        $policy = $this->policies[$entityType] ?? null;
        if ($policy instanceof CommentPolicy) {
            return $policy->canEditOwn($comment, $userId);
        }
        return true;
    }

    /**
     * Validate edit rules if a policy is present. Default: allow (no-op).
     */
    public function validateEdit(string $entityType, CommentDto $comment, int $userId, string $newBody): void
    {
        $policy = $this->policies[$entityType] ?? null;
        if ($policy instanceof CommentPolicy) {
            $policy->validateEdit($comment, $userId, $newBody);
        }
    }

    /**
     * Minimum allowed body length for the given entity type. Default: null (no limit)
     */
    public function getMinBodyLength(string $entityType): ?int
    {
        $policy = $this->policies[$entityType] ?? null;
        if ($policy instanceof CommentPolicy) {
            return $policy->getMinBodyLength();
        }
        return null;
    }

    /**
     * Maximum allowed body length for the given entity type. Default: null (no limit)
     */
    public function getMaxBodyLength(string $entityType): ?int
    {
        $policy = $this->policies[$entityType] ?? null;
        if ($policy instanceof CommentPolicy) {
            return $policy->getMaxBodyLength();
        }
        return 155;
    }
}
