<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Services;

use App\Domains\Calendar\Private\Models\Activity;
use Carbon\CarbonImmutable;

class ActivityStateService
{
    public const STATE_DRAFT = 'draft';
    public const STATE_PREVIEW = 'preview';
    public const STATE_ACTIVE = 'active';
    public const STATE_ENDED = 'ended';
    public const STATE_ARCHIVED = 'archived';

    public function computeState(Activity $a): string
    {
        $now = CarbonImmutable::now();
        if (!$a->preview_starts_at || $a->preview_starts_at->greaterThan($now)) {
            return self::STATE_DRAFT;
        }
        if ($a->archived_at && $a->archived_at->lessThanOrEqualTo($now)) {
            return self::STATE_ARCHIVED;
        }
        if ($a->active_ends_at && $a->active_ends_at->lessThanOrEqualTo($now)) {
            return self::STATE_ENDED;
        }
        if (!$a->active_starts_at || $a->active_starts_at->greaterThan($now)) {
            return self::STATE_PREVIEW;
        }
        return self::STATE_ACTIVE;
    }
}
