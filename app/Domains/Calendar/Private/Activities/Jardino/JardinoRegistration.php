<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino;

use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;

class JardinoRegistration implements ActivityRegistrationInterface
{
    public function displayComponentKey(): string
    {
        return 'calendar.activities.jardino';
    }

    public function configComponentKey(): ?string
    {
        return 'calendar.activities.jardino-config';
    }
}
