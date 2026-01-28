<?php

namespace App\Domains\Statistics\Public\DTOs;

use DateTimeInterface;

readonly class StatisticValue
{
    public function __construct(
        public string $statisticKey,
        public float|int|null $value,
        public ?DateTimeInterface $computedAt = null,
        public ?array $metadata = null,
    ) {}
}
