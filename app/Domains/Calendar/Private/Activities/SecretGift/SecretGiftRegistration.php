<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift;

use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;

class SecretGiftRegistration implements ActivityRegistrationInterface
{
    public const ACTIVITY_TYPE = 'secret-gift';

    public function displayComponentKey(): string
    {
        return 'secret-gift::secret-gift-component';
    }

    public function configComponentKey(): ?string
    {
        return null;
    }
}
