<?php

declare(strict_types=1);

namespace App\Domains\Moderation\Private\Support;

use App\Domains\Media\Public\Contracts\MediaUsageProvider;
use App\Domains\Moderation\Private\Models\ModerationReport;

/**
 * Claims every file a report snapshot kept (`content_snapshot._media`), so the
 * Media GC keeps it for as long as the report row exists.
 */
final class ReportMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        foreach (ModerationReport::query()->whereNotNull('content_snapshot')->cursor() as $report) {
            foreach ($report->content_snapshot['_media'] ?? [] as $path) {
                yield $path;
            }
        }
    }
}
