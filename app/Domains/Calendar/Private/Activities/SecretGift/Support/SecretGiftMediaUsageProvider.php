<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Support;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Media\Public\Contracts\MediaUsageProvider;

/**
 * Reports every stored gift image path so Media GC never collects a file a
 * gift still points at. Must stay registered: Media skips the whole
 * `secret-gift/` root when no provider claims anything under it, so without
 * this the orphans left by a replace, a removal or a re-shuffle pile up forever.
 *
 * Gift sounds are raw `local` files, not Media paths — they are not reported.
 */
final class SecretGiftMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        return SecretGiftAssignment::query()
            ->whereNotNull('gift_image_path')
            ->pluck('gift_image_path')
            ->all();
    }
}
