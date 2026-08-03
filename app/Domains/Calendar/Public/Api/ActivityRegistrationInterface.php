<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Api;

interface ActivityRegistrationInterface
{
    public function displayComponentKey(): string;

    public function configComponentKey(): ?string;

    /**
     * Validation rules merged into ActivityRequest for this type.
     *
     * @return array<string,mixed>
     */
    public function configRules(): array;

    /**
     * Persist the type's own config. Runs inside the activity's transaction.
     *
     * @param array<string,mixed> $validated
     */
    public function persistConfig(int $activityId, array $validated): void;
}
