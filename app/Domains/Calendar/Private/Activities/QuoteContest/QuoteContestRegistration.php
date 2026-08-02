<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest;

use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;

class QuoteContestRegistration implements ActivityRegistrationInterface
{
    public const ACTIVITY_TYPE = 'quote-contest';

    public function displayComponentKey(): string
    {
        return 'quote-contest::quote-contest-component';
    }

    public function configComponentKey(): ?string
    {
        return 'quote-contest::quote-contest-config';
    }

    public function configRules(): array
    {
        return [];
    }

    public function persistConfig(int $activityId, array $validated): void
    {
        // The config panel is still a placeholder; nothing to persist yet.
    }
}
