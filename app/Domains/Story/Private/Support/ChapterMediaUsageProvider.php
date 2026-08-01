<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Support;

use App\Domains\Media\Public\Contracts\MediaUsageProvider;
use App\Domains\Story\Private\Models\Chapter;

/**
 * Reports every image path a chapter still references — the `path` of every
 * image block in `content_blocks` — so the Media GC never collects a file a
 * chapter uses.
 *
 * Registering this provider is what makes `chapters/{userId}` a *claimed*
 * folder: the GC only spares a folder in which no path at all is claimed, so
 * once one chapter image is reported, every unreported original in that same
 * author's folder becomes deletable. Under-reporting destroys user data;
 * over-reporting merely leaks a file. When in doubt, report.
 *
 * Soft-deleted chapters are included on purpose (`withTrashed()`): their blocks
 * survive the delete, and sweeping their images would make a restore bring back
 * a chapter full of dead images.
 */
final class ChapterMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        $chapters = Chapter::query()
            ->withTrashed()
            ->whereNotNull('content_blocks')
            ->select(['id', 'content_blocks'])
            ->lazyById(200);

        foreach ($chapters as $chapter) {
            foreach ((array) $chapter->content_blocks as $block) {
                if (($block['type'] ?? null) === 'image' && !empty($block['path'])) {
                    yield $block['path'];
                }
            }
        }
    }
}
