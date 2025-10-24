<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Services;

use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal;
use App\Domains\Story\Public\Api\StoryPublicApi;

final class JardinoProgressService
{
    public function __construct(
        private readonly StoryPublicApi $storyApi,
    ) {}

    /**
     * Calculate total words written across all stories for a goal
     */
    public function calculateTotalWordsWritten(JardinoGoal $goal): int
    {
        $totalWords = 0;

        foreach ($goal->storySnapshots as $snapshot) {
            $totalWords += $snapshot->current_word_count - $snapshot->initial_word_count;
        }

        return max(0, $totalWords); // Ensure non-negative
    }

    /**
     * Calculate progress percentage based on target
     */
    public function calculateProgressPercentage(JardinoGoal $goal): float
    {
        $wordsWritten = $this->calculateTotalWordsWritten($goal);
        $target = $goal->target_word_count;

        if ($target <= 0) {
            return 0.0;
        }

        return min(100.0, ($wordsWritten / $target) * 100);
    }

    /**
     * Update snapshot word count when a chapter event occurs
     */
    public function updateSnapshotWordCount(int $storyId, int $wordDelta): void
    {
        // Find all goals that have this story as current target
        $goals = JardinoGoal::query()
            ->where('story_id', $storyId)
            ->with(['currentStorySnapshot' => function ($query) use ($storyId) {
                $query->where('story_id', $storyId);
            }])
            ->get();

        foreach ($goals as $goal) {
            $currentSnapshot = $goal->currentStorySnapshot;

            if ($currentSnapshot) {
                // Update existing snapshot
                $newWordCount = $currentSnapshot->current_word_count + $wordDelta;

                // Update biggest count if this is higher than before
                if ($newWordCount > $currentSnapshot->biggest_word_count) {
                    $currentSnapshot->biggest_word_count = $newWordCount;
                }

                $currentSnapshot->current_word_count = $newWordCount;
                $currentSnapshot->save();
            }
        }
    }

   
}
