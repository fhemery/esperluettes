<?php

namespace App\Domains\Story\ViewModels;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoryListViewModel
{
    /** @var StorySummaryViewModel[] */
    private array $items;

    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        array $items
    ) {
        /** @var StorySummaryViewModel[] $items */
        $this->items = $items;
    }

    /**
     * @return StorySummaryViewModel[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function links(): string
    {
        return (string) $this->paginator->links();
    }
}
