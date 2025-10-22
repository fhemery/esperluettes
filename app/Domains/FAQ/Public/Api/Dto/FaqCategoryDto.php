<?php

namespace App\Domains\FAQ\Public\Api\Dto;

use App\Domains\FAQ\Private\Models\FaqCategory;

class FaqCategoryDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $sortOrder,
        public bool $isActive,
        public int $createdByUserId,
        public int $updatedByUserId,
    ) {}

    public static function fromModel(FaqCategory $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
            description: $category->description,
            sortOrder: $category->sort_order,
            isActive: $category->is_active,
            createdByUserId: $category->created_by_user_id,
            updatedByUserId: $category->updated_by_user_id,
        );
    }
}
