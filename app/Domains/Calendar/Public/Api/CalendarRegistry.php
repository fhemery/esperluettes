<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Api;

class CalendarRegistry
{
    /**
     * Map of typeKey => ActivityRegistrationInterface
     * @var array<string,ActivityRegistrationInterface>
     */
    private array $types = [];

    public function register(string $typeKey, ActivityRegistrationInterface $registration): void
    {
        $this->types[$typeKey] = $registration;
    }

    public function has(string $typeKey): bool
    {
        return array_key_exists($typeKey, $this->types);
    }

    public function get(string $typeKey): ActivityRegistrationInterface
    {
        return $this->types[$typeKey];
    }
}
