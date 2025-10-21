<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Controllers;

use App\Domains\Calendar\Private\Services\ActivityService;
use Illuminate\Contracts\View\View as ViewContract;

class CalendarController
{
    public function __construct(
        private readonly ActivityService $activities
    ) {}

    public function show(string $slug): ViewContract
    {
        $activity = $this->activities->findVisibleBySlugOrFail($slug);

        return view('calendar::activity.show', [
            'activity' => $activity,
        ]);
    }
}
