<?php

namespace App\Domains\FAQ\Public\Api\Dto;

use App\Domains\FAQ\Private\Models\FaqQuestion;

class FaqQuestionDto
{
    public function __construct(
        public int $id,
        public int $faqCategoryId,
        public string $question,
        public string $slug,
        public string $answer,
        public ?string $imagePath,
        public ?string $imageAltText,
        public int $sortOrder,
        public bool $isActive,
        public int $createdByUserId,
        public int $updatedByUserId,
    ) {}

    public static function fromModel(FaqQuestion $question): self
    {
        return new self(
            id: $question->id,
            faqCategoryId: $question->faq_category_id,
            question: $question->question,
            slug: $question->slug,
            answer: $question->answer,
            imagePath: $question->image_path,
            imageAltText: $question->image_alt_text,
            sortOrder: $question->sort_order,
            isActive: $question->is_active,
            createdByUserId: $question->created_by_user_id,
            updatedByUserId: $question->updated_by_user_id,
        );
    }
}
