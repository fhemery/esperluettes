<?php

namespace App\Domains\Shared\ViewModels;

class RefViewModel
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        $desc = $this->description;
        if ($desc === null) {
            return null;
        }
        $trimmed = trim((string)$desc);
        return $trimmed !== '' ? $trimmed : null;
    }
}
