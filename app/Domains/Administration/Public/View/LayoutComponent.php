<?php

namespace App\Domains\Administration\Public\View;

use Illuminate\View\Component;

class LayoutComponent extends Component
{
    public function render()
    {
        return view('administration::layouts.layout');
    }
}

