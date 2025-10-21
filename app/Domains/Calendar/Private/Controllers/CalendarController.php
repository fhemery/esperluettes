<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Controllers;

use App\Domains\Calendar\Private\Services\ActivityService;
use Illuminate\Contracts\View\View as ViewContract;
use App\Domains\Calendar\Public\Api\CalendarRegistry;

class CalendarController
{
    public function __construct(
        private readonly ActivityService $activities
    ) {}

    public function show(string $slug): ViewContract
    {
        $activity = $this->activities->findVisibleBySlugOrFail($slug);
        /** @var CalendarRegistry $registry */
        $registry = app(CalendarRegistry::class);
        $componentKey = $registry->get($activity->activity_type)->displayComponentKey();

        return view('calendar::activity.show', [
            'activity' => $activity,
            'componentKey' => $componentKey,
        ]);
    }
}
