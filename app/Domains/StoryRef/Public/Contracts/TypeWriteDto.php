<?php

namespace App\Domains\StoryRef\Public\Contracts;

class TypeWriteDto
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly bool $is_active,
        public readonly ?int $order,
    ) {}
}
