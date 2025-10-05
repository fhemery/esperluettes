<?php

namespace App\Domains\Shared\Views\Components;

use App\Domains\Shared\Contracts\BreadcrumbRegistry;
use Illuminate\View\Component;
use Illuminate\View\View;

class BreadcrumbsComponent extends Component
{
    /** @var array<int, \App\Domains\Shared\Dto\BreadcrumbDto> */
    public array $items;

    /** @param array<int, \App\Domains\Shared\Dto\BreadcrumbDto> $items */
    public function __construct(array $items = [])
    {
        if (empty($items)) {
            /** @var BreadcrumbRegistry $registry */
            $registry = app(BreadcrumbRegistry::class);
            $this->items = $registry->generateForRequest(request());
        } else {
            $this->items = $items;
        }
    }

    public function render(): View
    {
        if (count($this->items) <= 1) {
            return view('shared::components.breadcrumbs-empty');
        }
        return view('shared::components.breadcrumbs');
    }
}
