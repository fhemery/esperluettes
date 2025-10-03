<?php

namespace App\Domains\Shared\Support;

class BreadcrumbTrail
{
    /** @var array<int,Breadcrumb> */
    private array $items = [];

    public function push(string $label, ?string $url = null, bool $active = false, ?string $icon = null): self
    {
        $this->items[] = new Breadcrumb($label, $url, $active, $icon);
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
     * @return array<int,Breadcrumb>
     */
    public function all(): array
    {
        return $this->items;
    }
}
