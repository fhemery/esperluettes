<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Public\Api\StoryMapperHelper;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginatedStoryDto
{
    /** @var StoryDto[] $data */
    public function __construct(
        public array $data,
        public StoriesPaginationDto $pagination,
    ) {
    }

    public static function from(
        LengthAwarePaginator $paginator,
        StoryQueryFieldsToReturnDto $fieldsToReturn,
        StoryMapperHelper $helper
    ): self
    {
        $items = [];
        foreach ($paginator->items() as $story) {
            $items[] = StoryDto::fromModel($story, $fieldsToReturn, $helper);
        }
        $pagination = StoriesPaginationDto::from($paginator);

        return new self(
            data: $items,
            pagination: $pagination,
        );
    }
}
