<?php

namespace App\Domains\Comment\Contracts;

class CommentListDto
{
    /** @param CommentDto[] $items */
    public function __construct(
        public readonly string $entityType,
        public readonly string $entityId,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly array $items = [],
    ) {
    }

    public static function empty(string $entityType, string $entityId, int $page = 1, int $perPage = 20): self
    {
        return new self(
            entityType: $entityType,
            entityId: $entityId,
            page: $page,
            perPage: $perPage,
            total: 0,
            items: [],
        );
    }

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'items' => array_map(fn(CommentDto $c) => $c->toArray(), $this->items),
        ];
    }
}
