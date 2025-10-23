<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\View\Components;

use App\Domains\Calendar\Private\Services\ActivityService;
use App\Domains\Calendar\Public\Contracts\ActivityDto;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

class ActivityListComponent extends Component
{
    /** @var array<ActivityDto> */
    public array $activities = [];

    public function __construct(
        private ActivityService $activityService
    ) {
        $this->hydrate();
    }

    private function hydrate(): void
    {
        $activityModels = $this->activityService->getAllActivitiesSortedByState();
        
        $this->activities = $activityModels
            ->map(fn($activity) => ActivityDto::fromModel($activity))
            ->toArray();
    }

    public function render(): ViewContract
    {
        return view('calendar::components.activity-list', [
            'activities' => $this->activities,
        ]);
    }
}
