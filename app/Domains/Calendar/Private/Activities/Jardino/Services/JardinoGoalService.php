<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Services;

use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal;
use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoStorySnapshot;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Support\Facades\DB;

final class JardinoGoalService
{
    public function __construct(
        private readonly StoryPublicApi $storyApi,
    ) {}

    public function createOrUpdateGoal(int $activityId, int $userId, int $storyId, int $targetWordCount): JardinoGoal
    {
        return DB::transaction(function () use ($activityId, $userId, $storyId, $targetWordCount) {
            /** @var JardinoGoal|null $existing */
            $existing = JardinoGoal::query()->where('activity_id', $activityId)->where('user_id', $userId)->first();
            if ($existing) {
                $existing->story_id = $storyId;
                $existing->target_word_count = $targetWordCount;
                $existing->save();

                // Create initial snapshot if story changed
                $this->createInitialSnapshot($existing);

                return $existing;
            }

            $goal = JardinoGoal::create([
                'activity_id' => $activityId,
                'user_id' => $userId,
                'story_id' => $storyId,
                'target_word_count' => $targetWordCount,
            ]);

            // Create initial snapshot for new goal
            $this->createInitialSnapshot($goal);

            return $goal;
        });
    }

    private function createInitialSnapshot(JardinoGoal $goal): void
    {
        // Check if there's already an active snapshot for this story
        $existingSnapshot = JardinoStorySnapshot::query()
            ->where('goal_id', $goal->id)
            ->where('story_id', $goal->story_id)
            ->whereNull('deselected_at')
            ->first();

        if ($existingSnapshot) {
            return; // Already have an active snapshot
        }

        // Get current story info
        $storyDto = $this->storyApi->getStory($goal->story_id);
        if (!$storyDto) {
            return; // Story doesn't exist or was deleted
        }

        // Create initial snapshot
        JardinoStorySnapshot::create([
            'goal_id' => $goal->id,
            'story_id' => $goal->story_id,
            'story_title' => $storyDto->title,
            'initial_word_count' => $storyDto->word_count,
            'current_word_count' => $storyDto->word_count,
            'biggest_word_count' => $storyDto->word_count,
            'selected_at' => now(),
        ]);
    }
}
