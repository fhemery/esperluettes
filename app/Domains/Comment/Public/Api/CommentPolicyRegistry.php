<?php

namespace App\Domains\Comment\Public\Api;

use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use App\Domains\Comment\Public\Api\Contracts\CommentPolicy;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Comment\Public\Api\Contracts\DefaultCommentPolicy;

class CommentPolicyRegistry
{
    /** @var array<string, CommentPolicy> */
    private array $policies = [];
    private DefaultCommentPolicy $defaultPolicy;

    public function __construct()
    {
        $this->defaultPolicy = new DefaultCommentPolicy();
    }

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
        $this->getPolicy($dto->entityType)->validateCreate($dto);
    }

    /**
     * Whether the user can create a root comment. Default: true when no policy.
     */
    public function canCreateRoot(string $entityType, int $entityId, int $userId): bool
    {
        return $this->getPolicy($entityType)->canCreateRoot($entityId, $userId);
    }

    /**
     * Whether the user can reply to the given parent comment. Default: true.
     */
    public function canReply(string $entityType, CommentDto $parentComment, int $userId): bool
    {
        return $this->getPolicy($entityType)->canReply($parentComment, $userId);
    }

    /**
     * Whether the user can see the Edit control for their own comment. Default: true.
     */
    public function canEditOwn(string $entityType, CommentDto $comment, int $userId): bool
    {
        return $this->getPolicy($entityType)->canEditOwn($comment, $userId);
    }

    /**
     * Validate edit rules if a policy is present. Default: allow (no-op).
     */
    public function validateEdit(string $entityType, CommentDto $comment, int $userId, string $newBody): void
    {
        $this->getPolicy($entityType)->validateEdit($comment, $userId, $newBody);
    }

    /**
     * Minimum allowed root comment length for the given entity type. Default: null (no limit)
     */
    public function getRootCommentMinLength(string $entityType): ?int
    {
        return $this->getPolicy($entityType)->getRootCommentMinLength();
    }

    /**
     * Maximum allowed root comment length for the given entity type. Default: null (no limit)
     */
    public function getRootCommentMaxLength(string $entityType): ?int
    {
        return $this->getPolicy($entityType)->getRootCommentMaxLength();
    }

    /**
     * Minimum allowed reply comment length for the given entity type. Default: null (no limit)
     */
    public function getReplyCommentMinLength(string $entityType): ?int
    {
        return $this->getPolicy($entityType)->getReplyCommentMinLength();
    }

    /**
     * Maximum allowed reply comment length for the given entity type. Default: null (no limit)
     */
    public function getReplyCommentMaxLength(string $entityType): ?int
    {
        return $this->getPolicy($entityType)->getReplyCommentMaxLength();
    }

    /**
     * Generate URL to view the comment in context. Default: null (no URL)
     */
    public function getUrl(string $entityType, int $entityId, int $commentId): ?string
    {
        return $this->getPolicy($entityType)->getUrl($entityId, $commentId);
    }

    /**
     * Whether the entity type carries annotations at all. Default: false
     */
    public function supportsAnnotations(string $entityType): bool
    {
        return $this->getPolicy($entityType)->supportsAnnotations();
    }

    /**
     * Whether the user can attach annotations to their root comment. Default: false
     */
    public function canAnnotate(string $entityType, int $entityId, int $userId): bool
    {
        return $this->getPolicy($entityType)->canAnnotate($entityId, $userId);
    }

    /**
     * Whether the user can mark annotations on the entity as processed. Default: false
     */
    public function canMarkAsProcessed(string $entityType, int $entityId, int $userId): bool
    {
        return $this->getPolicy($entityType)->canMarkAsProcessed($entityId, $userId);
    }

    /**
     * Maximum allowed annotation body length. Default: 1000
     */
    public function getAnnotationBodyMaxLength(string $entityType): ?int
    {
        return $this->getPolicy($entityType)->getAnnotationBodyMaxLength();
    }

    /**
     * Maximum allowed annotation highlighted text length. Default: 500
     */
    public function getAnnotationHighlightMaxLength(string $entityType): ?int
    {
        return $this->getPolicy($entityType)->getAnnotationHighlightMaxLength();
    }


    private function getPolicy(string $entityType): CommentPolicy
    {
        return $this->policies[$entityType] ?? $this->defaultPolicy;
    }
}
