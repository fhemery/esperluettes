<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Services;

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\CategoryNotEmptyException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Everything the admin can configure on a contest: the two dates that ride
 * along with the activity form, and the categories.
 *
 * Controllers and view components go through this service; neither touches a
 * model.
 */
class QuoteContestConfigService
{
    /**
     * Create or update the contest's settings row.
     *
     * Only the two configurable dates are written: the four `notified_*_at`
     * markers belong to the notification scheduler, and an admin moving a date
     * must not re-arm a broadcast that already fired.
     */
    public function saveSettings(int $activityId, mixed $submissionsEndAt, mixed $votesStartAt): QuoteContestSettings
    {
        return QuoteContestSettings::query()->updateOrCreate(
            ['activity_id' => $activityId],
            [
                'submissions_end_at' => Carbon::parse($submissionsEndAt),
                'votes_start_at' => Carbon::parse($votesStartAt),
            ],
        );
    }

    public function settingsFor(int $activityId): ?QuoteContestSettings
    {
        return QuoteContestSettings::query()->where('activity_id', $activityId)->first();
    }

    /**
     * The contest's categories in display order, each carrying its entry count
     * (withdrawn included — that count is what forbids deletion).
     *
     * @return Collection<int,QuoteContestCategory>
     */
    public function categoriesFor(int $activityId): Collection
    {
        return QuoteContestCategory::query()
            ->where('activity_id', $activityId)
            ->withCount('entries')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function addCategory(int $activityId, string $title, ?string $description, ?int $position = null): QuoteContestCategory
    {
        return QuoteContestCategory::create([
            'activity_id' => $activityId,
            'title' => $title,
            'description' => $description,
            'position' => $position ?? $this->nextPosition($activityId),
        ]);
    }

    public function updateCategory(
        int $activityId,
        int $categoryId,
        string $title,
        ?string $description,
        ?int $position = null,
    ): QuoteContestCategory {
        $category = $this->findCategoryOrFail($activityId, $categoryId);

        $category->fill([
            'title' => $title,
            'description' => $description,
            'position' => $position ?? $category->position,
        ])->save();

        return $category;
    }

    /**
     * @throws CategoryNotEmptyException when the category still holds an entry
     */
    public function deleteCategory(int $activityId, int $categoryId): void
    {
        $category = $this->findCategoryOrFail($activityId, $categoryId);

        // A withdrawn entry is still evidence, so it counts (decision #5).
        if ($category->entries()->exists()) {
            throw CategoryNotEmptyException::make();
        }

        $category->delete();
    }

    private function findCategoryOrFail(int $activityId, int $categoryId): QuoteContestCategory
    {
        return QuoteContestCategory::query()
            ->where('activity_id', $activityId)
            ->findOrFail($categoryId);
    }

    private function nextPosition(int $activityId): int
    {
        $max = QuoteContestCategory::query()->where('activity_id', $activityId)->max('position');

        return ((int) $max) + 1;
    }
}
