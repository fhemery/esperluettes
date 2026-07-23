<?php

namespace App\Domains\Comment\Public\View\Components;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

class AnnotableComponent extends Component
{
    public function __construct(
        public string $entityType,
        public int $entityId,
        public bool $canAnnotate = false,
        public ?string $viewerRole = null,
    ) {
    }

    public function render(): ViewContract
    {
        return view('comment::components.annotable');
    }
}
