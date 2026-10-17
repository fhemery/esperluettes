<?php

declare(strict_types=1);

namespace App\Domains\Fanart\Private\Support;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Media\Public\Contracts\MediaUsageProvider;

/**
 * Claims the file of every fanart row, soft-deleted ones included (so a
 * reactivated account finds its files). Must stay registered: Media skips the
 * whole `fanart/` root when no provider claims anything under it.
 */
final class FanartMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        foreach (Fanart::withTrashed()->pluck('file_path') as $path) {
            yield $path;
        }
    }
}
