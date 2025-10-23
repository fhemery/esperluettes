<?php

namespace App\Domains\FAQ\Private\Services;

use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use Illuminate\Support\Facades\Cache;

class FaqCache
{
    public function getActiveCategoriesOrdered()
    {
        return Cache::remember('faq:categories:active', 3600, function () {
            return FaqCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']);
        });
    }

    public function getActiveQuestionsForCategorySlug(string $categorySlug)
    {
        $key = 'faq:questions:by-slug:' . $categorySlug;
        return Cache::remember($key, 3600, function () use ($categorySlug) {
            $category = FaqCategory::query()
                ->where('is_active', true)
                ->where('slug', $categorySlug)
                ->first(['id']);

            if (!$category) {
                return collect();
            }

            return FaqQuestion::query()
                ->where('faq_category_id', $category->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'question', 'slug', 'answer', 'image_path', 'image_alt_text']);
        });
    }

    public function clear(): void
    {
        Cache::flush();
    }
}
