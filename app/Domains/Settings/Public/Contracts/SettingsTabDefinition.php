<?php

namespace App\Domains\Settings\Public\Contracts;

final class SettingsTabDefinition
{
    public function __construct(
        public readonly string $id,           // Unique tab identifier (e.g., 'story', 'notification')
        public readonly int $order,           // Display order (lower = first)
        public readonly string $nameKey,      // Full translation key for tab name
        public readonly ?string $icon = null, // Optional Material Symbols icon name
    ) {}
}
