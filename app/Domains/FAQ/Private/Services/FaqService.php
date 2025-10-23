<?php

namespace App\Domains\FAQ\Private\Services;

use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Private\Services\FaqCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class FaqService
{
    public function __construct(private readonly FaqCache $cache)
    {
    }

    public function createCategory(array $data): FaqCategory
    {
        $user = Auth::user();

        $categoryData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 999,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ];

        // Allow explicit slug if provided
        if (isset($data['slug'])) {
            $categoryData['slug'] = $data['slug'];
        }

        $category = FaqCategory::create($categoryData);
        $this->cache->clear();
        return $category;
    }

    public function updateCategory(int $categoryId, array $data): FaqCategory
    {
        $user = Auth::user();

        /** @var FaqCategory $category */
        $category = FaqCategory::query()->findOrFail($categoryId);

        $updateData = [
            'updated_by_user_id' => $user->id,
        ];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['slug'])) {
            $updateData['slug'] = $data['slug'];
        }

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        if (isset($data['sort_order'])) {
            $updateData['sort_order'] = $data['sort_order'];
        }

        $category->update($updateData);
        $this->cache->clear();
        return $category->fresh();
    }

    public function deleteCategory(int $categoryId): void
    {
        /** @var FaqCategory $category */
        $category = FaqCategory::query()->findOrFail($categoryId);
        $category->delete();
        $this->cache->clear();
    }

    public function createQuestion(array $data): FaqQuestion
    {
        $user = Auth::user();

        // Validate category exists
        if (!FaqCategory::query()->where('id', $data['faq_category_id'])->exists()) {
            throw ValidationException::withMessages([
                'faq_category_id' => ['The specified category does not exist.'],
            ]);
        }

        $questionData = [
            'faq_category_id' => $data['faq_category_id'],
            'question' => $data['question'],
            'answer' => Purifier::clean($data['answer'], 'admin-content'),
            'image_path' => $data['image_path'] ?? null,
            'image_alt_text' => $data['image_alt_text'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 999,
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ];

        // Allow explicit slug if provided
        if (isset($data['slug'])) {
            $questionData['slug'] = $data['slug'];
        }

        $question = FaqQuestion::create($questionData);
        $this->cache->clear();
        return $question;
    }

    public function updateQuestion(int $questionId, array $data): FaqQuestion
    {
        $user = Auth::user();

        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);

        // Validate category exists if moving
        if (!FaqCategory::query()->where('id', $data['faq_category_id'])->exists()) {
            throw ValidationException::withMessages([
                'faq_category_id' => ['The specified category does not exist.'],
            ]);
        }

        $updateData = [
            'faq_category_id' => $data['faq_category_id'],
            'question' => $data['question'],
            'slug' => $data['slug'],
            'answer' => Purifier::clean($data['answer'], 'admin-content'),
            'image_path' => $data['image_path'],
            'image_alt_text' => $data['image_alt_text'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'updated_by_user_id' => $user->id,
        ];

        $question->update($updateData);
        $this->cache->clear();
        return $question->fresh();
    }

    public function deleteQuestion(int $questionId): void
    {
        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);
        $question->delete();
        $this->cache->clear();
    }

    public function activateQuestion(int $questionId): void
    {
        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);
        $question->update(['is_active' => true]);
        $this->cache->clear();
    }

    public function deactivateQuestion(int $questionId): void
    {
        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);
        $question->update(['is_active' => false]);
        $this->cache->clear();
    }

    /**
     * Get active FAQ categories ordered by sort_order for frontend display.
     */
    public function getActiveCategoriesOrdered()
    {
        return $this->cache->getActiveCategoriesOrdered();
    }

    /**
     * Get active questions for an active category identified by slug, ordered by sort_order.
     */
    public function getActiveQuestionsForCategorySlug(string $categorySlug)
    {
        return $this->cache->getActiveQuestionsForCategorySlug($categorySlug);
    }
}
