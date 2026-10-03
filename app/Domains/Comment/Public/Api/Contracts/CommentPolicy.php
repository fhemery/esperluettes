<?php

namespace App\Domains\Comment\Public\Api\Contracts;

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
     * Minimum allowed body length for root comments for this entity type.
     * Default implementation should return null (no limit).
     */
    public function getRootCommentMinLength(): ?int;

    /**
     * Maximum allowed body length for root comments for this entity type.
     * Default implementation should return null (no limit).
     */
    public function getRootCommentMaxLength(): ?int;

    /**
     * Minimum allowed body length for reply comments for this entity type.
     * Default implementation should return null (no limit).
     */
    public function getReplyCommentMinLength(): ?int;

    /**
     * Maximum allowed body length for reply comments for this entity type.
     * Default implementation should return null (no limit).
     */
    public function getReplyCommentMaxLength(): ?int;

    /**
     * Generate URL to view the comment in context.
     * Should return null if entity doesn't exist or URL cannot be generated.
     */
    public function getUrl(int $entityId, int $commentId): ?string;

    /**
     * Whether this entity type carries annotations at all — whatever the viewer.
     * Gates the annotation UI and its script on the comment list. Default: false
     */
    public function supportsAnnotations(): bool;

    /**
     * Whether the user can attach annotations (anchored on the entity's text) to their root comment.
     * Default: false (the entity type is not annotatable).
     */
    public function canAnnotate(int $entityId, int $userId): bool;

    /**
     * Whether the user can mark annotations on this entity as processed.
     * Default: false
     */
    public function canMarkAsProcessed(int $entityId, int $userId): bool;

    /**
     * Maximum allowed annotation body length. Default: 1000
     */
    public function getAnnotationBodyMaxLength(): ?int;

    /**
     * Maximum allowed highlighted text length for a root annotation. Default: 500
     */
    public function getAnnotationHighlightMaxLength(): ?int;
}
