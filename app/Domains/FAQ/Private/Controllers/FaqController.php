<?php

namespace App\Domains\FAQ\Private\Controllers;

use Illuminate\Routing\Controller;
use App\Domains\FAQ\Private\Services\FaqService;
use App\Domains\FAQ\Private\ViewModels\FaqPageViewModel;
use App\Domains\FAQ\Private\ViewModels\FaqTabViewModel;

class FaqController extends Controller
{
    public function __construct(
        private readonly FaqService $faq
    ) {}

    public function index(?string $categorySlug = null)
    {
        $categories = $this->faq->getActiveCategoriesOrdered();

        $tabs = $categories->map(fn($c) => new FaqTabViewModel(
            key: (string) $c->slug,
            label: (string) $c->name,
        ))->values()->all();

        // Determine initial tab: requested slug if it exists among active categories; otherwise first
        $initial = $categories->first()?->slug;
        if ($categorySlug) {
            $match = $categories->firstWhere('slug', $categorySlug);
            if ($match) {
                $initial = $match->slug;
            }
        }

        $questions = $initial ? $this->faq->getActiveQuestionsForCategorySlug($initial) : collect();

        // Resolve current category name (for SEO)
        $currentCategoryName = null;
        if ($initial) {
            $match = $categories->firstWhere('slug', $initial);
            $currentCategoryName = $match?->name;
        }
        // Fallback: if no slug matched but categories exist, use first category's name for SEO
        if (!$currentCategoryName && $categories->isNotEmpty()) {
            $currentCategoryName = $categories->first()->name;
        }

        // Compute page SEO/title
        $siteName = config('app.name');
        $categoryForSeo = $currentCategoryName; // may be null
        $pageTitle = isset($categoryForSeo)
            ? ('FAQ - ' . $categoryForSeo . ' - ' . $siteName)
            : ('FAQ - ' . $siteName);
        $pageDescription = isset($categoryForSeo)
            ? __('faq::index.seo_description', ['category' => $categoryForSeo, 'site' => $siteName])
            : __('faq::index.seo_description', ['category' => '', 'site' => $siteName]);

        $vm = new FaqPageViewModel($tabs, $initial, $pageTitle, $pageDescription);

        return view('faq::pages.index', compact('vm', 'questions'));
    }
}
