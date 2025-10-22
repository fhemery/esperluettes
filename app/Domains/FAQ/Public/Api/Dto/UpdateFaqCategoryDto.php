<?php

namespace App\Domains\FAQ\Public\Api\Dto;

class UpdateFaqCategoryDto
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description,
        public bool $isActive,
        public int $sortOrder,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];
    }
}
