<?php

namespace App\Domains\StoryRef\Public\Contracts;

class CopyrightWriteDto
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $is_active,
        public readonly ?int $order,
    ) {}
}
