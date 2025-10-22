<?php

namespace App\Domains\FAQ\Private\Services;

use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class FaqService
{
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

        return FaqCategory::create($categoryData);
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

        return $category->fresh();
    }

    public function deleteCategory(int $categoryId): void
    {
        /** @var FaqCategory $category */
        $category = FaqCategory::query()->findOrFail($categoryId);
        $category->delete();
    }

    /**
     * Reorder categories by updating their sort_order based on array position.
     *
     * @param array<int> $categoryIds Array of category IDs in desired order
     * @throws ValidationException
     */
    public function reorderCategories(array $categoryIds): void
    {
        // Validation
        if (empty($categoryIds)) {
            throw ValidationException::withMessages([
                'category_ids' => ['The category IDs array cannot be empty.'],
            ]);
        }

        // Check for duplicates
        if (count($categoryIds) !== count(array_unique($categoryIds))) {
            throw ValidationException::withMessages([
                'category_ids' => ['The category IDs array contains duplicates.'],
            ]);
        }

        // Verify all categories exist
        $existingCount = FaqCategory::query()->whereIn('id', $categoryIds)->count();
        if ($existingCount !== count($categoryIds)) {
            throw ValidationException::withMessages([
                'category_ids' => ['One or more category IDs do not exist.'],
            ]);
        }

        // Update sort_order in a transaction
        DB::transaction(function () use ($categoryIds) {
            foreach ($categoryIds as $index => $categoryId) {
                FaqCategory::query()
                    ->where('id', $categoryId)
                    ->update(['sort_order' => $index]);
            }
        });
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

        return FaqQuestion::create($questionData);
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

        return $question->fresh();
    }

    public function deleteQuestion(int $questionId): void
    {
        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);
        $question->delete();
    }

    public function activateQuestion(int $questionId): void
    {
        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);
        $question->update(['is_active' => true]);
    }

    public function deactivateQuestion(int $questionId): void
    {
        /** @var FaqQuestion $question */
        $question = FaqQuestion::query()->findOrFail($questionId);
        $question->update(['is_active' => false]);
    }

    /**
     * Reorder questions within a specific category.
     *
     * @param int $categoryId
     * @param array<int> $questionIds Array of question IDs in desired order
     * @throws ValidationException
     */
    public function reorderQuestionsInCategory(int $categoryId, array $questionIds): void
    {
        // Validate category exists
        if (!FaqCategory::query()->where('id', $categoryId)->exists()) {
            throw ValidationException::withMessages([
                'category_id' => ['The specified category does not exist.'],
            ]);
        }

        // Validation
        if (empty($questionIds)) {
            throw ValidationException::withMessages([
                'question_ids' => ['The question IDs array cannot be empty.'],
            ]);
        }

        // Check for duplicates
        if (count($questionIds) !== count(array_unique($questionIds))) {
            throw ValidationException::withMessages([
                'question_ids' => ['The question IDs array contains duplicates.'],
            ]);
        }

        // Verify all questions exist and belong to the category
        $existingCount = FaqQuestion::query()
            ->whereIn('id', $questionIds)
            ->where('faq_category_id', $categoryId)
            ->count();

        if ($existingCount !== count($questionIds)) {
            throw ValidationException::withMessages([
                'question_ids' => ['One or more question IDs do not exist or do not belong to this category.'],
            ]);
        }

        // Update sort_order in a transaction
        DB::transaction(function () use ($questionIds) {
            foreach ($questionIds as $index => $questionId) {
                FaqQuestion::query()
                    ->where('id', $questionId)
                    ->update(['sort_order' => $index]);
            }
        });
    }
}
