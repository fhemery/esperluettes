<?php

declare(strict_types=1);

namespace App\Domains\StaticPage\Private\Support;

use App\Domains\Media\Public\Contracts\MediaUsageProvider;
use App\Domains\StaticPage\Private\Models\StaticPage;

/**
 * Reports every static-page header image path so the Media GC never collects a
 * file still referenced by a page. Grandfathered dated paths
 * (static-pages/YYYY/MM/…) are reported too — the sweep ignores them anyway.
 */
final class StaticPageMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        return StaticPage::query()
            ->whereNotNull('header_image_path')
            ->pluck('header_image_path')
            ->all();
    }
}
