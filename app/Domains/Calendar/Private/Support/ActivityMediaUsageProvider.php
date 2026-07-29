<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Support;

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Media\Public\Contracts\MediaUsageProvider;

/**
 * Reports every activity image path so the Media GC never collects a file
 * still referenced by an activity. Grandfathered dated paths
 * (activities/YYYY/MM/…) are reported too — the sweep ignores them anyway.
 */
final class ActivityMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        return Activity::query()
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->all();
    }
}
