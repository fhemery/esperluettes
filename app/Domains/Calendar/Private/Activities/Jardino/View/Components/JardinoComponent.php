<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Components;

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoObjectiveViewModel;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class JardinoComponent extends Component
{
    public function __construct(
        public Activity $activity,
        private readonly StoryPublicApi $stories
    ) {}

    public function render(): View
    {
        $userId = (int) Auth::id();

        $objective = null; // new JardinoObjectiveViewModel(...) when implemented later
        $stories = $this->stories->getStoriesForUser($userId, excludeCoauthored: false);
        $vm = new JardinoViewModel($objective, $stories);

        return view('jardino::components.jardino', compact('vm'));
    }
}
