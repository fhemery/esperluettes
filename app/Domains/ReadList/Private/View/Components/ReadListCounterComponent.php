<?php

namespace App\Domains\ReadList\Private\View\Components;

use App\Domains\ReadList\Private\Services\ReadListService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

class ReadListCounterComponent extends Component
{
    public int $count = 0;

    public function __construct(
        public int $storyId,
        private ReadListService $readListService
    ) {
        $this->count = $this->readListService->countReadersForStory($this->storyId);
    }

    public function render(): ViewContract
    {
        return view('read-list::components.read-list-counter', [
            'count' => $this->count,
        ]);
    }
}
