<?php

namespace App\Domains\Statistics\Public\DTOs;

use DateTimeInterface;

readonly class TimeSeriesPoint
{
    public function __construct(
        public DateTimeInterface $periodStart,
        public string $granularity,
        public float|int $value,
        public float|int|null $cumulativeValue = null,
    ) {}
}
