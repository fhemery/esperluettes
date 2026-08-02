<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers;

use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Requests\SaveCategoryRequest;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestConfigService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\CategoryNotEmptyException;
use Illuminate\Http\RedirectResponse;

/**
 * Category CRUD for the admin. The contest's two dates are not here: they ride
 * along with the activity form through the plugin config contract.
 */
class QuoteContestCategoryController
{
    public function __construct(
        private readonly QuoteContestConfigService $config,
    ) {}

    public function store(SaveCategoryRequest $request, int $activityId): RedirectResponse
    {
        $data = $request->validated();

        $this->config->addCategory(
            $activityId,
            $data['title'],
            $data['description'] ?? null,
            isset($data['position']) ? (int) $data['position'] : null,
        );

        return $this->backToActivity($activityId)
            ->with('success', __('quote-contest::quote-contest.flash.category_created'));
    }

    public function update(SaveCategoryRequest $request, int $activityId, int $categoryId): RedirectResponse
    {
        $data = $request->validated();

        $this->config->updateCategory(
            $activityId,
            $categoryId,
            $data['title'],
            $data['description'] ?? null,
            isset($data['position']) ? (int) $data['position'] : null,
        );

        return $this->backToActivity($activityId)
            ->with('success', __('quote-contest::quote-contest.flash.category_updated'));
    }

    public function destroy(int $activityId, int $categoryId): RedirectResponse
    {
        try {
            $this->config->deleteCategory($activityId, $categoryId);
        } catch (CategoryNotEmptyException $e) {
            return $this->backToActivity($activityId)->with('error', $e->getMessage());
        }

        return $this->backToActivity($activityId)
            ->with('success', __('quote-contest::quote-contest.flash.category_deleted'));
    }

    private function backToActivity(int $activityId): RedirectResponse
    {
        return redirect()->route('calendar.admin.activities.edit', $activityId);
    }
}
