<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino;

use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;

class JardinoRegistration implements ActivityRegistrationInterface
{
    public const ACTIVITY_TYPE = 'jardino';

    public function displayComponentKey(): string
    {
        return 'jardino::jardino-component';
    }

    public function configComponentKey(): ?string
    {
        return null;
    }
}
