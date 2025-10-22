<?php

namespace App\Domains\FAQ\Public\Api\Dto;

class UpdateFaqQuestionDto
{
    public function __construct(
        public int $faqCategoryId,
        public string $question,
        public string $slug,
        public string $answer,
        public ?string $imagePath,
        public ?string $imageAltText,
        public bool $isActive,
        public int $sortOrder,
    ) {}

    public function toArray(): array
    {
        return [
            'faq_category_id' => $this->faqCategoryId,
            'question' => $this->question,
            'slug' => $this->slug,
            'answer' => $this->answer,
            'image_path' => $this->imagePath,
            'image_alt_text' => $this->imageAltText,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];
    }
}
