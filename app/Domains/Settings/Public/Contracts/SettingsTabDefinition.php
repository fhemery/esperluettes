<?php

namespace App\Domains\Settings\Public\Contracts;

final class SettingsTabDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly int $order,
        public readonly string $nameKey,
        public readonly ?string $icon = null,
        public readonly ?string $customViewPath = null,
    ) {}
}
