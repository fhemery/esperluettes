<?php

namespace App\Domains\Shared\Dto;

class BreadcrumbTrailDto
{
    /** @var array<int,BreadcrumbDto> */
    private array $items = [];

    public function push(string $label, ?string $url = null, bool $active = false, ?string $icon = null): self
    {
        $this->items[] = new BreadcrumbDto($label, $url, $active, $icon);
        return $this;
    }

    /**
     * Mark the last item as active.
     */
    public function markLastActive(): void
    {
        if (!empty($this->items)) {
            $this->items[count($this->items) - 1]->active = true;
        }
    }

    /**
     * @return array<int,BreadcrumbDto>
     */
    public function all(): array
    {
        return $this->items;
    }
}
