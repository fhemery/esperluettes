<?php

namespace App\Domains\Story\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Services\StoryViewModelBuilder;
use App\Domains\Story\Private\ViewModels\StorySummaryViewModel;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class RandomStoriesComponent extends Component
{
    /** @var array<StorySummaryViewModel> */
    public array $vms = [];

    public function __construct(private StoryService $storiesService, private StoryViewModelBuilder $builder,
    private AuthPublicApi $authApi)
    {
        $this->hydrate();
    }

    private function hydrate(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            $this->vms = [];
            return;
        }
        
        // We want the Community stories if user hass USER_CONFIRMED role
        $visibilities = [Story::VIS_PUBLIC];
        if ($this->authApi->hasAnyRole([Roles::USER_CONFIRMED])) {
            $visibilities[] = Story::VIS_COMMUNITY;
        }
        $stories = $this->storiesService->getRandomStories((int)$userId, 7, $visibilities);
        $this->vms = $this->builder->buildStorySummaryItems($stories);
    }

    public function render(): ViewContract
    {
        return view('story::components.random-stories', [
            'vms' => $this->vms,
        ]);
    }
}
