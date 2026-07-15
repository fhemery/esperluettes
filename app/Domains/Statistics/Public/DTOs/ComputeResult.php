<?php

namespace App\Domains\Statistics\Public\DTOs;

final readonly class ComputeResult
{
    public function __construct(
        public float|int|null $snapshotValue,
        public int $timeSeriesPoints = 0,
        public int $eventsProcessed = 0,
    ) {}
}
