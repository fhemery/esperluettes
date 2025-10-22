<?php

use App\Domains\FAQ\Public\Api\FaqPublicApi;
use App\Domains\FAQ\Public\Api\Dto\FaqCategoryDto;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqCategoryDto;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Create a FAQ category through the PublicApi as the given user.
 */
function createFaqCategory(
    string $name,
    ?string $description = null,
    ?bool $isActive = null,
    ?int $sortOrder = null
): FaqCategoryDto {
    $api = app(FaqPublicApi::class);
    
    $dto = new CreateFaqCategoryDto(
        name: $name,
        description: $description,
        isActive: $isActive,
        sortOrder: $sortOrder,
    );
    
    return $api->createCategory($dto);
}

/**
 * Get a FAQ category by ID.
 */
function getFaqCategory(int $categoryId): FaqCategoryDto
{
    $api = app(FaqPublicApi::class);
    return $api->getCategory($categoryId);
}
