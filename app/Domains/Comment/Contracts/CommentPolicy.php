<?php

namespace App\Domains\Comment\Contracts;

interface CommentPolicy
{
    /**
     * Validate whether the given user can create a (root or reply) comment, based on the provided DTO.
     * Should throw an exception (e.g., UnauthorizedException or ValidationException) if not allowed.
     */
    public function validateCreate(CommentToCreateDto $dto): void;

    /**
     * Whether the current user can create a root comment for the given entity.
     * Default: true
     */
    public function canCreateRoot(int $entityId, int $userId): bool;

    /**
     * Whether the current user can reply to the given parent comment.
     * Default: true
     */
    public function canReply(CommentDto $parentComment, int $userId): bool;

    /**
     * Whether the current user can edit their own comment (visibility purpose in UI).
     * Default: true
     */
    public function canEditOwn(CommentDto $comment, int $userId): bool;

    /**
     * Validate whether an edit is allowed. Should throw if not allowed.
     * Rules may differ from create.
     */
    public function validateEdit(CommentDto $comment, int $userId, string $newBody): void;

    /**
     * Minimum allowed body length for comments for this entity type.
     * Default implementation should return null (no limit).
     */
    public function getMinBodyLength(): ?int;

    /**
     * Maximum allowed body length for comments for this entity type.
     * Default implementation should return null (no limit).
     */
    public function getMaxBodyLength(): ?int;
}
