<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Tests\Support;

use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;
use RuntimeException;

class TestActivityRegistration implements ActivityRegistrationInterface
{
    /**
     * Recorded persistConfig() calls: list of ['activityId' => int, 'validated' => array].
     *
     * @var array<int,array{activityId:int,validated:array<string,mixed>}>
     */
    public array $persistedCalls = [];

    /** When true, persistConfig() throws instead of recording. */
    public bool $throwOnPersist = false;

    /**
     * @param array<string,mixed> $rules
     */
    public function __construct(
        private string $display,
        private ?string $config = null,
        private array $rules = [],
    ) {}

    public function displayComponentKey(): string
    {
        return $this->display;
    }

    public function configComponentKey(): ?string
    {
        return $this->config;
    }

    public function configRules(): array
    {
        return $this->rules;
    }

    public function persistConfig(int $activityId, array $validated): void
    {
        if ($this->throwOnPersist) {
            throw new RuntimeException('persistConfig failed');
        }

        $this->persistedCalls[] = ['activityId' => $activityId, 'validated' => $validated];
    }
}
