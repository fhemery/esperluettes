<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Services;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Support\Str;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;

class ActivityService
{
    public function __construct(
        private readonly AuthPublicApi $auth,
    ) {}
    public function create(array $data): Activity
    {
        // Create, then set slug with id suffix
        $activity = new Activity();
        $activity->name = (string) $data['name'];
        $activity->slug = 'tmp';
        $activity->description = $data['description'] ?? null;
        $activity->image_path = $data['image_path'] ?? null;
        $activity->activity_type = (string) $data['activity_type'];
        $activity->role_restrictions = $data['role_restrictions'] ?? [];
        $activity->requires_subscription = (bool) ($data['requires_subscription'] ?? false);
        $activity->max_participants = $data['max_participants'] ?? null;
        $activity->preview_starts_at = $data['preview_starts_at'] ?? null;
        $activity->active_starts_at = $data['active_starts_at'] ?? null;
        $activity->active_ends_at = $data['active_ends_at'] ?? null;
        $activity->archived_at = $data['archived_at'] ?? null;
        $activity->created_by_user_id = $data['created_by_user_id'] ?? null;
        $activity->save();

        $activity->slug = Str::slug($activity->name) . '-' . $activity->id;
        $activity->save();

        return $activity;
    }

    public function findById(int $id): ?Activity
    {
        return Activity::query()->find($id);
    }

    public function update(Activity $activity, array $data): Activity
    {
        // Full replace: set all updateable fields (activity_type assumed immutable at higher layer)
        $activity->name = (string) $data['name'];
        $activity->description = $data['description'] ?? null;
        $activity->image_path = $data['image_path'] ?? null;
        $activity->role_restrictions = $data['role_restrictions'] ?? [];
        $activity->requires_subscription = (bool) ($data['requires_subscription'] ?? false);
        $activity->max_participants = $data['max_participants'] ?? null;
        $activity->preview_starts_at = $data['preview_starts_at'] ?? null;
        $activity->active_starts_at = $data['active_starts_at'] ?? null;
        $activity->active_ends_at = $data['active_ends_at'] ?? null;
        $activity->archived_at = $data['archived_at'] ?? null;
        $activity->save();

        // If name changed, keep slug pattern in sync (do not change id suffix)
        $activity->slug = Str::slug($activity->name) . '-' . $activity->id;
        $activity->save();

        return $activity;
    }

    public function delete(Activity $activity): void
    {
        $activity->delete();
    }

    /**
     * Find a visible activity by slug or fail with 404.
     * Visibility rules:
     * - Must not be DRAFT or ARCHIVED
     * - Current user must have at least one role in activity role_restrictions
     */
    public function findVisibleBySlugOrFail(string $slug): Activity
    {
        /** @var Activity $activity */
        $activity = Activity::query()->where('slug', $slug)->firstOrFail();

        // Check role restrictions
        $allowedRoles = (array) ($activity->role_restrictions ?? []);
        if (! $this->auth->hasAnyRole($allowedRoles)) {
            abort(404);
        }

        // Check state visibility
        $state = $activity->state;
        if ($state === \App\Domains\Calendar\Public\Contracts\ActivityState::DRAFT
            || $state === \App\Domains\Calendar\Public\Contracts\ActivityState::ARCHIVED) {
            abort(404);
        }

        return $activity;
    }

    /**
     * Get all activities sorted by state priority and start date.
     * Order: active (by end date asc), preview (by start date asc), ended (by end date desc)
     * Excludes draft and archived activities.
     * 
     * @return \Illuminate\Support\Collection<Activity>
     */
    public function getAllActivitiesSortedByState(): \Illuminate\Support\Collection
    {
        $activities = Activity::query()
            ->whereNotNull('preview_starts_at')
            ->get();

        // Separate by state
        $active = [];
        $preview = [];
        $ended = [];

        foreach ($activities as $activity) {
            // Role-based visibility: must match activity role_restrictions exactly (no admin bypass)
            $allowedRoles = (array) ($activity->role_restrictions ?? []);
            if (! $this->auth->hasAnyRole($allowedRoles)) {
                continue;
            }
            $state = $activity->state;
            if ($state === \App\Domains\Calendar\Public\Contracts\ActivityState::ACTIVE) {
                $active[] = $activity;
            } elseif ($state === \App\Domains\Calendar\Public\Contracts\ActivityState::PREVIEW) {
                $preview[] = $activity;
            } elseif ($state === \App\Domains\Calendar\Public\Contracts\ActivityState::ENDED) {
                $ended[] = $activity;
            }
            // Skip DRAFT and ARCHIVED
        }

        // Sort each group
        usort($active, fn($a, $b) => 
            ($a->active_ends_at ?? now()->addYears(10)) <=> ($b->active_ends_at ?? now()->addYears(10))
        );
        usort($preview, fn($a, $b) => 
            ($a->active_starts_at ?? now()->addYears(10)) <=> ($b->active_starts_at ?? now()->addYears(10))
        );
        usort($ended, fn($a, $b) => 
            ($b->active_ends_at ?? now()->subYears(10)) <=> ($a->active_ends_at ?? now()->subYears(10))
        );

        // Merge in order
        return collect([...$active, ...$preview, ...$ended]);
    }
}
