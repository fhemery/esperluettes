<?php

namespace App\Domains\FAQ\Public\Api\Dto;

class CreateFaqCategoryDto
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?bool $isActive = null,
        public ?int $sortOrder = null,
    ) {}

    public function toArray(): array
    {
        $data = ['name' => $this->name];
        
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        
        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }
        
        if ($this->sortOrder !== null) {
            $data['sort_order'] = $this->sortOrder;
        }
        
        return $data;
    }
}
