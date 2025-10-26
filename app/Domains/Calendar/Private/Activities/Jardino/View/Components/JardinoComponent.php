<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Components;

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\JardinoObjectiveViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal;
use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoProgressService;
use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoFlowerService;
use App\Domains\Calendar\Private\Services\ActivityService;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\GardenMapViewModel;
use App\Domains\Calendar\Private\Activities\Jardino\View\Models\GardenCellViewModel;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class JardinoComponent extends Component
{
    public function __construct(
        public Activity $activity,
        private readonly StoryPublicApi $stories,
        private readonly JardinoProgressService $progressService,
        private readonly JardinoFlowerService $flowerService,
        private readonly ProfilePublicApi $profileApi,
        private readonly ActivityService $activityService,
    ) {}

    public function render(): View
    {
        $userId = (int) Auth::id();

        $goal = JardinoGoal::query()
            ->where('activity_id', $this->activity->id)
            ->where('user_id', $userId)
            ->with('storySnapshots')
            ->first();

        $objective = null;
        if ($goal) {
            $storyDto = $this->stories->getStory($goal->story_id);

            // Calculate progress statistics
            $wordsWritten = $this->progressService->calculateTotalWordsWritten($goal);
            $progressPercentage = $this->progressService->calculateProgressPercentage($goal);

            // Calculate flower statistics
            $flowerStats = $this->flowerService->calculateAvailableFlowers($goal);

            $objective = new JardinoObjectiveViewModel(
                storyId: $goal->story_id,
                storyTitle: (string) ($storyDto?->title ?? ''),
                targetWordCount: $goal->target_word_count,
                wordsWritten: $wordsWritten,
                progressPercentage: $progressPercentage,
                flowersEarned: $flowerStats['earned'],
                flowersPlanted: $flowerStats['planted'],
                flowersAvailable: $flowerStats['available'],
            );
        }

        $stories = $this->stories->getStoriesForUser($userId, excludeCoauthored: false);

        // Get garden map data
        $rawGardenData = $this->flowerService->getGardenMapData($this->activity->id);
        $occupiedCells = [];
        $userIds = [];

        foreach ($rawGardenData as $cellData) {
            if (isset($cellData['user_id']) && $cellData['user_id']) {
                $userIds[] = $cellData['user_id'];
            }
        }

        // Fetch user profiles for all users who have planted flowers
        $profileDtos = $this->profileApi->getPublicProfiles(array_unique($userIds));

        foreach ($rawGardenData as $cellData) {
            $occupiedCells[] = new GardenCellViewModel(
                x: $cellData['x'],
                y: $cellData['y'],
                type: $cellData['type'],
                flowerImage: $cellData['flower_image'],
                userId: $cellData['user_id'],
                plantedAt: $cellData['planted_at'],
                displayName: isset($cellData['user_id']) ? ($profileDtos[$cellData['user_id']]->display_name ?? null) : null,
                avatarUrl: isset($cellData['user_id']) ? ($profileDtos[$cellData['user_id']]->avatar_url ?? null) : null,
            );
        }

        $gardenMap = new GardenMapViewModel(
            occupiedCells: $occupiedCells
        );

        $vm = new JardinoViewModel($this->activity->id, $objective, $stories, $gardenMap);

        return view('jardino::components.jardino', compact('vm'));
    }
}
