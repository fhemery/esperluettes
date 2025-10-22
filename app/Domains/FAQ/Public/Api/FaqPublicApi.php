<?php

namespace App\Domains\FAQ\Public\Api;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Private\Services\FaqService;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqCategoryDto;
use App\Domains\FAQ\Public\Api\Dto\CreateFaqQuestionDto;
use App\Domains\FAQ\Public\Api\Dto\FaqCategoryDto;
use App\Domains\FAQ\Public\Api\Dto\FaqQuestionDto;
use App\Domains\FAQ\Public\Api\Dto\UpdateFaqCategoryDto;
use App\Domains\FAQ\Public\Api\Dto\UpdateFaqQuestionDto;
use Illuminate\Auth\Access\AuthorizationException;

class FaqPublicApi
{
    public function __construct(
        private AuthPublicApi $authApi,
        private FaqService $faqService,
    ) {}

    /**
     * Create a new FAQ category (admin or tech-admin only).
     *
     * @param CreateFaqCategoryDto $dto
     * @return FaqCategoryDto
     * @throws AuthorizationException
     */
    public function createCategory(CreateFaqCategoryDto $dto): FaqCategoryDto
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to create FAQ categories.');
        }

        $category = $this->faqService->createCategory($dto->toArray());

        return FaqCategoryDto::fromModel($category);
    }

    /**
     * Update an existing FAQ category (admin or tech-admin only).
     * This is a complete update - all fields are replaced.
     *
     * @param int $categoryId
     * @param UpdateFaqCategoryDto $dto
     * @return FaqCategoryDto
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateCategory(int $categoryId, UpdateFaqCategoryDto $dto): FaqCategoryDto
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to update FAQ categories.');
        }

        $category = $this->faqService->updateCategory($categoryId, $dto->toArray());

        return FaqCategoryDto::fromModel($category);
    }

    /**
     * Get a FAQ category by ID.
     *
     * @param int $categoryId
     * @return FaqCategoryDto
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getCategory(int $categoryId): FaqCategoryDto
    {
        /** @var FaqCategory $category */
        $category = FaqCategory::query()->findOrFail($categoryId);

        return FaqCategoryDto::fromModel($category);
    }

    /**
     * Delete an FAQ category and cascade delete its questions (admin or tech-admin only).
     *
     * @param int $categoryId
     * @return void
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteCategory(int $categoryId): void
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to delete FAQ categories.');
        }

        $this->faqService->deleteCategory($categoryId);
    }

    /**
     * Reorder FAQ categories (admin or tech-admin only).
     *
     * @param array<int> $categoryIds Array of category IDs in desired order
     * @return void
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reorderCategories(array $categoryIds): void
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to reorder FAQ categories.');
        }

        $this->faqService->reorderCategories($categoryIds);
    }

    /**
     * Create a new FAQ question (admin or tech-admin only).
     *
     * @param CreateFaqQuestionDto $dto
     * @return FaqQuestionDto
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createQuestion(CreateFaqQuestionDto $dto): FaqQuestionDto
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to create FAQ questions.');
        }

        $question = $this->faqService->createQuestion($dto->toArray());

        return FaqQuestionDto::fromModel($question);
    }

    /**
     * Update an existing FAQ question (admin or tech-admin only).
     * This is a complete update - all fields are replaced.
     *
     * @param int $questionId
     * @param UpdateFaqQuestionDto $dto
     * @return FaqQuestionDto
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateQuestion(int $questionId, UpdateFaqQuestionDto $dto): FaqQuestionDto
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to update FAQ questions.');
        }

        $question = $this->faqService->updateQuestion($questionId, $dto->toArray());

        return FaqQuestionDto::fromModel($question);
    }

    /**
     * Get a FAQ question by ID.
     *
     * @param int $questionId
     * @return FaqQuestionDto
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getQuestion(int $questionId): FaqQuestionDto
    {
        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);

        return FaqQuestionDto::fromModel($question);
    }

    /**
     * Delete an FAQ question (admin or tech-admin only).
     *
     * @param int $questionId
     * @return void
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteQuestion(int $questionId): void
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to delete FAQ questions.');
        }

        $this->faqService->deleteQuestion($questionId);
    }

    /**
     * Activate an FAQ question (admin or tech-admin only).
     *
     * @param int $questionId
     * @return void
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function activateQuestion(int $questionId): void
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to activate FAQ questions.');
        }

        $this->faqService->activateQuestion($questionId);
    }

    /**
     * Deactivate an FAQ question (admin or tech-admin only).
     *
     * @param int $questionId
     * @return void
     * @throws AuthorizationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deactivateQuestion(int $questionId): void
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to deactivate FAQ questions.');
        }

        $this->faqService->deactivateQuestion($questionId);
    }

    /**
     * Reorder FAQ questions within a specific category (admin or tech-admin only).
     *
     * @param int $categoryId
     * @param array<int> $questionIds Array of question IDs in desired order
     * @return void
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reorderQuestionsInCategory(int $categoryId, array $questionIds): void
    {
        if (!$this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to reorder FAQ questions.');
        }

        $this->faqService->reorderQuestionsInCategory($categoryId, $questionIds);
    }
}
