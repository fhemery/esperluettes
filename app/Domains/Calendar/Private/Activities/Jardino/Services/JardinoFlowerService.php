<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Services;

use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGardenCell;
use App\Domains\Calendar\Private\Activities\Jardino\Models\JardinoGoal;
use App\Domains\Calendar\Private\Services\ActivityService;
use App\Domains\Calendar\Private\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class JardinoFlowerService
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    /**
     * Calculate total flowers earned based on progress and daily limits
     */
    public function calculateAvailableFlowers(JardinoGoal $goal): array
    {
        $activity = $this->activityService->findById($goal->activity_id);

        if (!$activity) {
            return [
                'available' => 0,
                'earned' => 0,
                'planted' => 0,
                'progress_flowers' => 0,
                'daily_limit_flowers' => 0,
            ];
        }

        // Calculate progress-based flowers (5% increments, max 25 at 125%)
        $flowerEligibleWords = (int) $goal->storySnapshots()
            ->sum(DB::raw('biggest_word_count - initial_word_count'));

        $progressFlowers = $this->calculateProgressFlowers($flowerEligibleWords, $goal->target_word_count);

        // Calculate daily limit flowers
        $dailyLimitFlowers = $this->calculateDailyLimitFlowers($activity);

        // Available flowers is the minimum of progress flowers and daily limit
        $availableFlowers = min($progressFlowers, $dailyLimitFlowers, 25);

        // Get planted flowers count
        $plantedFlowers = $goal->plantedFlowers()->count();

        return [
            'available' => max(0, $availableFlowers - $plantedFlowers),
            'earned' => $availableFlowers,
            'planted' => $plantedFlowers,
            'progress_flowers' => $progressFlowers,
            'daily_limit_flowers' => $dailyLimitFlowers,
        ];
    }

    /**
     * Calculate flowers based on 5% progress increments
     */
    private function calculateProgressFlowers(int $flowerEligibleWords, int $targetWordCount): int
    {
        if ($targetWordCount <= 0) {
            return 0;
        }

        $percentage = ($flowerEligibleWords / $targetWordCount) * 100;
        return (int) floor($percentage / 5); // 5% increments
    }

    /**
     * Calculate daily limit flowers (2 per day since activity start)
     */
    private function calculateDailyLimitFlowers(Activity $activity): int
    {
        $startDate = Carbon::parse($activity->active_starts_at, 'CET');
        $now = Carbon::now('CET');

        // If activity hasn't started yet, return 0
        if ($now->isBefore($startDate)) {
            return 0;
        }

        $daysSinceStart = $startDate->diffInDays($now) + 1; // min 1 on start day
        return (int) min(2 * $daysSinceStart, 25); // 2 flowers per day, max 25
    }

    /**
     * Plant a flower (decrease available count)
     */
    public function plantFlower(int $activityId, int $userId, int $x, int $y, string $flowerImage): void
    {
        DB::transaction(function () use ($activityId, $userId, $x, $y, $flowerImage) {
            // Check if cell is available
            $existingCell = JardinoGardenCell::query()
                ->where('activity_id', $activityId)
                ->where('x', $x)
                ->where('y', $y)
                ->first();

            if ($existingCell) {
                throw new \Exception('Cell is already occupied');
            }

            // Check if user has available flowers
            $goal = JardinoGoal::query()
                ->where('activity_id', $activityId)
                ->where('user_id', $userId)
                ->first();

            if (!$goal) {
                throw new \Exception('No goal found for user');
            }

            $flowerStats = $this->calculateAvailableFlowers($goal);
            if ($flowerStats['available'] <= 0) {
                throw new \Exception('No flowers available to plant');
            }

            // Plant the flower
            JardinoGardenCell::create([
                'activity_id' => $activityId,
                'x' => $x,
                'y' => $y,
                'type' => 'flower',
                'flower_image' => $flowerImage,
                'user_id' => $userId,
                'planted_at' => now(),
            ]);
        });
    }

    /**
     * Remove a planted flower (increase available count)
     */
    public function removeFlower(int $activityId, int $userId, int $x, int $y): void
    {
        $cell = JardinoGardenCell::query()
            ->where('activity_id', $activityId)
            ->where('x', $x)
            ->where('y', $y)
            ->where('user_id', $userId)
            ->where('type', 'flower')
            ->first();

        if (!$cell) {
            throw new \Exception('Flower not found or does not belong to user');
        }

        $cell->delete();
    }

    /**
     * Get all planted flowers and blocked cells for garden map display (raw data)
     */
    public function getGardenMapData(int $activityId): array
    {
        $cells = JardinoGardenCell::query()
            ->where('activity_id', $activityId)
            ->get();

        $occupiedCells = [];
        foreach ($cells as $cell) {
            $occupiedCells[] = [
                'x' => $cell->x,
                'y' => $cell->y,
                'type' => $cell->type,
                'flower_image' => $cell->flower_image,
                'user_id' => $cell->user_id,
                'planted_at' => $cell->planted_at,
            ];
        }

        return $occupiedCells;
    }
    public function blockCell(int $activityId, int $x, int $y): void
    {
        DB::transaction(function () use ($activityId, $x, $y) {
            // Check if cell is already occupied or blocked
            $existingCell = JardinoGardenCell::query()
                ->where('activity_id', $activityId)
                ->where('x', $x)
                ->where('y', $y)
                ->first();

            if ($existingCell) {
                throw new \Exception('Cell is already occupied or blocked');
            }

            // Block the cell
            JardinoGardenCell::create([
                'activity_id' => $activityId,
                'x' => $x,
                'y' => $y,
                'type' => 'blocked',
                'flower_image' => null,
                'user_id' => null,
                'planted_at' => null,
            ]);
        });
    }

    /**
     * Unblock a cell (admin only)
     */
    public function unblockCell(int $activityId, int $x, int $y): void
    {
        $cell = JardinoGardenCell::query()
            ->where('activity_id', $activityId)
            ->where('x', $x)
            ->where('y', $y)
            ->where('type', 'blocked')
            ->first();

        if (!$cell) {
            throw new \Exception('Blocked cell not found');
        }

        $cell->delete();
    }
}
