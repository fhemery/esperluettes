<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Api;

interface ActivityRegistrationInterface
{
    public function displayComponentKey(): string;

    public function configComponentKey(): ?string;
}
