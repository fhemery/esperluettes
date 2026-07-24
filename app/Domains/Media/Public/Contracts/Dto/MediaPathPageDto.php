<?php

declare(strict_types=1);

namespace App\Domains\Media\Public\Contracts\Dto;

/**
 * A page of the reuse-picker listing.
 */
final class MediaPathPageDto
{
    /** @param array<int, MediaPathDto> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly bool $hasMore,
    ) {}

    /** @return array{items:array<int,array{path:string,url:string}>,page:int,hasMore:bool} */
    public function toArray(): array
    {
        return [
            'items' => array_map(fn (MediaPathDto $i) => $i->toArray(), $this->items),
            'page' => $this->page,
            'hasMore' => $this->hasMore,
        ];
    }
}
