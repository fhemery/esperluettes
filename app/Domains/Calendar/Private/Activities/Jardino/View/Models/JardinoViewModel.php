<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Models;

use App\Domains\Story\Public\Contracts\UserStoryListItemDto;

final class JardinoViewModel
{
    /** @param array<int, UserStoryListItemDto> $stories */
    public function __construct(
        public readonly int $activityId,
        public readonly ?JardinoObjectiveViewModel $objective,
        public readonly array $stories,
    ) {}
}
