<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginatedStoryDto
{
    /** @param array<int, StoryDto> $data */
    public function __construct(
        public array $data,
        public StoriesPaginationDto $pagination,
    ) {
    }

    public static function from(
        LengthAwarePaginator $paginator,
        StoryQueryFieldsToReturnDto $fieldsToReturn,
        StoryRefLookupService $refService
    ): self
    {
        $items = [];
        foreach ($paginator->items() as $story) {
            $items[] = StoryDto::fromModel($story, $fieldsToReturn, $refService);
        }
        $pagination = StoriesPaginationDto::from($paginator);

        return new self(
            data: $items,
            pagination: $pagination,
        );
    }
}
