<?php

namespace App\Domains\Comment\Public\Api\Contracts;

class CommentToCreateDto
{
    /**
     * @param AnnotationToCreateDto[] $annotations Only allowed on a root comment.
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly string $body,
        public readonly ?int $parentCommentId,
        public readonly array $annotations = [],
    ) {}
}
