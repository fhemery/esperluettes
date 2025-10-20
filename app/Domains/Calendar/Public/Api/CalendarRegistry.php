<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Api;

class CalendarRegistry
{
    /** @var array<string,mixed> */
    private array $types = [];

    public function register(string $typeKey, mixed $activityType): void
    {
        $this->types[$typeKey] = $activityType;
    }

    public function has(string $typeKey): bool
    {
        return array_key_exists($typeKey, $this->types);
    }
}
