<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Support;

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Media\Public\Contracts\MediaUsageProvider;

/**
 * Reports every stored gift image and gift sound path so Media GC never
 * collects a file a gift still points at. Must stay registered: Media skips the
 * whole `secret-gift/` root when no provider claims anything under it, so
 * without this the orphans left by a replace, a removal or a re-shuffle pile up
 * forever.
 */
final class SecretGiftMediaUsageProvider implements MediaUsageProvider
{
    public function usedPaths(): iterable
    {
        $rows = SecretGiftAssignment::query()
            ->where(fn ($q) => $q->whereNotNull('gift_image_path')->orWhereNotNull('gift_sound_path'))
            ->get(['gift_image_path', 'gift_sound_path']);

        foreach ($rows as $row) {
            if ($row->gift_image_path !== null) {
                yield $row->gift_image_path;
            }
            if ($row->gift_sound_path !== null) {
                yield $row->gift_sound_path;
            }
        }
    }
}
