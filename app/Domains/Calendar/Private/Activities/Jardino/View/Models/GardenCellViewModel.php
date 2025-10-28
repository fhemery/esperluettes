<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Models;

use Carbon\Carbon;

final class GardenCellViewModel
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
        public readonly string $type,
        public readonly ?string $flowerImage,
        public readonly ?int $userId,
        public readonly ?Carbon $plantedAt,
        public readonly ?string $displayName = null,
        public readonly ?string $avatarUrl = null,
    ) {}
}
