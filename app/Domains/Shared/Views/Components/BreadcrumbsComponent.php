<?php

namespace App\Domains\Shared\Views\Components;

use App\Domains\Shared\ViewModels\BreadcrumbViewModel;
use Illuminate\View\Component;
use Illuminate\View\View;

class BreadcrumbsComponent extends Component
{
    /** @var array<int, \App\Domains\Shared\ViewModels\BreadcrumbPartViewModel> */
    public array $items = [];

    /** @param array<int, \App\Domains\Shared\ViewModels\BreadcrumbPartViewModel> $items */
    public function __construct(?BreadcrumbViewModel $breadcrumbs = null)
    {
        if ($breadcrumbs) {
            $this->items = $breadcrumbs->all();
        }
    }

    public function render(): View
    {
        if (empty($this->items)) {
            return view('shared::components.breadcrumbs-empty');
        }
        return view('shared::components.breadcrumbs');
    }
}
