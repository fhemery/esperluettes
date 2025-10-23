<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Services;

use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal;
use Illuminate\Support\Facades\DB;

final class JardinoGoalService
{
    public function createOrUpdateGoal(int $activityId, int $userId, int $storyId, int $targetWordCount): JardinoGoal
    {
        return DB::transaction(function () use ($activityId, $userId, $storyId, $targetWordCount) {
            /** @var JardinoGoal|null $existing */
            $existing = JardinoGoal::query()->where('activity_id', $activityId)->where('user_id', $userId)->first();
            if ($existing) {
                $existing->story_id = $storyId;
                $existing->target_word_count = $targetWordCount;
                $existing->save();
                return $existing;
            }
            return JardinoGoal::create([
                'activity_id' => $activityId,
                'user_id' => $userId,
                'story_id' => $storyId,
                'target_word_count' => $targetWordCount,
            ]);
        });
    }
}
