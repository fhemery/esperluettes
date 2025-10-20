<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Services;

use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Support\Str;

class ActivityService
{
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
}
