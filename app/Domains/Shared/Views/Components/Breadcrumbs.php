<?php

namespace App\Domains\Shared\Views\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Breadcrumbs extends Component
{
    /** @var array<int, \App\Domains\Shared\Support\Breadcrumb> */
    public array $items;

    /** @param array<int, \App\Domains\Shared\Support\Breadcrumb> $items */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function render(): View
    {
        return view('shared::components.breadcrumbs');
    }
}
