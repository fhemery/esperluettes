<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Http\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoGoalService;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use Illuminate\Http\RedirectResponse;
use App\Domains\Calendar\Private\Activities\Jardino\Http\Requests\CreateGoalRequest;

final class JardinoDashboardController
{
    public function __construct(
        private readonly JardinoGoalService $goals,
        private readonly AuthPublicApi $auth
    ) {}

    public function createGoal(CreateGoalRequest $request, Activity $activity): RedirectResponse
    {
        $user = $request->user();

        // Only allow JardiNo activity type and when activity is ACTIVE
        if ($activity->activity_type !== 'jardino' || $activity->state !== ActivityState::ACTIVE) {
            abort(404);
        }

        // Validate input
        $data = $request->validated();

        // Access rules: must be confirmed if activity requires it
        $restrictions = (array) ($activity->role_restrictions ?? []);
        if (!empty($restrictions) && ! $this->auth->hasAnyRole($restrictions)) {
            abort(403);
        }

        $this->goals->createOrUpdateGoal(
            activityId: (int) $activity->id,
            userId: (int) $user->id,
            storyId: (int) $data['story_id'],
            targetWordCount: (int) $data['target_word_count'],
        );

        return back()->with('success', __('jardino::details.flash.objective_saved'));
    }
}
