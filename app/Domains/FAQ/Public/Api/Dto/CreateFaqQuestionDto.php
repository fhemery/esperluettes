<?php

namespace App\Domains\FAQ\Public\Api\Dto;

class CreateFaqQuestionDto
{
    public function __construct(
        public int $faqCategoryId,
        public string $question,
        public string $answer,
        public ?string $imagePath = null,
        public ?string $imageAltText = null,
        public ?bool $isActive = null,
        public ?int $sortOrder = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'faq_category_id' => $this->faqCategoryId,
            'question' => $this->question,
            'answer' => $this->answer,
        ];
        
        if ($this->imagePath !== null) {
            $data['image_path'] = $this->imagePath;
        }
        
        if ($this->imageAltText !== null) {
            $data['image_alt_text'] = $this->imageAltText;
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
