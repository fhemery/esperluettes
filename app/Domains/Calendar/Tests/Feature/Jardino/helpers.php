<?php

declare(strict_types=1);

use Tests\TestCase;
use App\Domains\Calendar\Private\Models\Activity;

/**
 * Create an ACTIVE Jardino activity and return helper data.
 * Returns an object: { id: int, url: string }
 */
function createActiveJardino(TestCase $t, array $overrides = [], ?int $actorUserId = null): object
{
    $baseOverrides = [
        'name' => 'Jardino',
        'activity_type' => 'jardino',
        'preview_starts_at' => now()->subDay(),
        'active_starts_at' => now()->subHour(),
        'active_ends_at' => now()->addDay(),
    ];

    $id = createActivity($t, overrides: array_merge($baseOverrides, $overrides), actorUserId: $actorUserId);
    $activity = Activity::findOrFail($id);
    $url = route('calendar.activities.show', $activity->slug);

    return (object) [
        'id' => $id,
        'url' => $url,
    ];
}
