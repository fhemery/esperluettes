<?php

namespace App\Domains\Comment\Public\Api\Contracts;

class CommentToCreateDto
{
    public function __construct(
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly string $body,
        public readonly ?int $parentCommentId,
    ) {}
}
