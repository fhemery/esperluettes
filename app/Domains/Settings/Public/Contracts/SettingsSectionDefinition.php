<?php

namespace App\Domains\Settings\Public\Contracts;

final class SettingsSectionDefinition
{
    public function __construct(
        public readonly string $tabId,            // Reference to parent tab
        public readonly string $id,               // Unique section identifier within tab
        public readonly int $order,               // Display order within tab
        public readonly string $nameKey,          // Full translation key for section name
        public readonly ?string $descriptionKey = null, // Full translation key for description
    ) {}
}
