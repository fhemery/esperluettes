<?php

namespace App\Domains\Story\Private\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Services\StoryViewModelBuilder;
use App\Domains\Story\Private\ViewModels\StorySummaryViewModel;

class KeepWritingComponent extends Component
{
    public ?StorySummaryViewModel $vm = null;

    public function __construct(
        private readonly StoryService $storyService,
        private readonly StoryViewModelBuilder $builder,
    ) {
        $userId = Auth::id();
        $story = $this->storyService->getStoryByLatestAddedChapter($userId);
        if ($story) {
            $this->vm = $this->builder->buildStorySummaryItem($story);
        }
    }

    public function render(): View
    {
        return view('story::components.keep-writing', [
            'vm' => $this->vm,
        ]);
    }
}
