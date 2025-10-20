<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Tests\Support;

use App\Domains\Calendar\Public\Api\ActivityRegistrationInterface;

class TestActivityRegistration implements ActivityRegistrationInterface
{
    public function __construct(
        private string $display,
        private ?string $config = null,
    ) {}

    public function displayComponentKey(): string
    {
        return $this->display;
    }

    public function configComponentKey(): ?string
    {
        return $this->config;
    }
}
