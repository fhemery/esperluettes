<?php

namespace App\Domains\Story\Private\ViewModels;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoryListViewModel
{
    /** @var StorySummaryViewModel[] */
    private array $items;
    /** @var array<string, mixed> */
    private array $appends;

    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        array $items,
        array $appends = []
    ) {
        /** @var StorySummaryViewModel[] $items */
        $this->items = $items;
        $this->appends = $appends;
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

    public function links(?string $view = null, array $data = []): string
    {
        if (!empty($this->appends)) {
            return (string) $this->paginator->appends($this->appends)->links($view, $data);
        }
        return (string) $this->paginator->links($view, $data);
    }
}
