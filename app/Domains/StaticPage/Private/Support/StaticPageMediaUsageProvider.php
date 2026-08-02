<?php

declare(strict_types=1);

namespace App\Domains\StaticPage\Private\Support;

use App\Domains\Media\Public\Contracts\MediaUsageProvider;
use App\Domains\StaticPage\Private\Models\StaticPage;

/**
 * Reports every image path a static page still uses — the header image plus
 * every image block inside advanced pages' content_blocks — so the Media GC
 * never collects a file a page references. Grandfathered dated paths
 * (static-pages/YYYY/MM/…) are reported too — the sweep ignores them anyway.
 */
final class StaticPageMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        foreach (StaticPage::query()->whereNotNull('header_image_path')->pluck('header_image_path') as $path) {
            if ($path) {
                yield $path;
            }
        }

        foreach (StaticPage::query()->whereNotNull('content_blocks')->get(['content_blocks']) as $page) {
            foreach ((array) $page->content_blocks as $block) {
                if (($block['type'] ?? null) === 'image' && !empty($block['path'])) {
                    yield $block['path'];
                }
            }
        }
    }
}
