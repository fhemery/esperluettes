<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Models;

final class JardinoObjectiveViewModel
{
    public function __construct(
        public readonly int $storyId,
        public readonly string $storyTitle,
        public readonly int $targetWordCount,
        public readonly int $wordsWritten,
        public readonly float $progressPercentage,
        public readonly int $flowersEarned,
        public readonly int $flowersPlanted,
        public readonly int $flowersAvailable,
    ) {}
}
