<?php

declare(strict_types=1);

namespace App\Domains\News\Private\Support;

use App\Domains\Media\Public\Contracts\MediaUsageProvider;
use App\Domains\News\Private\Models\News;

/**
 * Reports every image path News still uses — the header image plus every image
 * block inside advanced articles' content_blocks — so the Media GC never
 * collects a file a News article references.
 */
final class NewsMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        foreach (News::query()->whereNotNull('header_image_path')->pluck('header_image_path') as $path) {
            if ($path) {
                yield $path;
            }
        }

        foreach (News::query()->whereNotNull('content_blocks')->get(['content_blocks']) as $news) {
            foreach ((array) $news->content_blocks as $block) {
                if (($block['type'] ?? null) === 'image' && !empty($block['path'])) {
                    yield $block['path'];
                }
            }
        }
    }
}
