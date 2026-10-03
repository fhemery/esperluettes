<?php

declare(strict_types=1);

namespace App\Domains\Comment\Public\Api\Contracts;

class AnnotationListDto
{
    public const ROLE_COMMENTER = 'commenter';
    public const ROLE_AUTHOR = 'author';
    public const ROLE_MODERATOR = 'moderator';

    /**
     * @param AnnotationDto[] $items Root annotations, oldest first
     * @param string $viewerRole One of the ROLE_* constants
     */
    public function __construct(
        public readonly int $commentId,
        public readonly string $viewerRole,
        public readonly array $items,
    ) {}

    public function toArray(): array
    {
        return [
            'comment_id' => $this->commentId,
            'viewer_role' => $this->viewerRole,
            'items' => array_map(fn (AnnotationDto $a) => $a->toArray(), $this->items),
        ];
    }
}
