<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Contracts;

final class ActivityState
{
    public const DRAFT = 'draft';
    public const PREVIEW = 'preview';
    public const ACTIVE = 'active';
    public const ENDED = 'ended';
    public const ARCHIVED = 'archived';
}
