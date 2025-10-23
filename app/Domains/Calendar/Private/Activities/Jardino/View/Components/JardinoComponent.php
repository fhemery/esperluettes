<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Components;

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoObjectiveViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal;
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

        $goal = JardinoGoal::query()
            ->where('activity_id', $this->activity->id)
            ->where('user_id', $userId)
            ->first();

        $objective = null;
        if ($goal) {
            $storyDto = $this->stories->getStory((int) $goal->story_id);
            $objective = new JardinoObjectiveViewModel(
                storyId: (int) $goal->story_id,
                storyTitle: (string) ($storyDto?->title ?? ''),
                targetWordCount: (int) $goal->target_word_count,
            );
        }

        $stories = $this->stories->getStoriesForUser($userId, excludeCoauthored: false);
        $vm = new JardinoViewModel($this->activity->id, $objective, $stories);

        return view('jardino::components.jardino', compact('vm'));
    }
}
