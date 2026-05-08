<?php

namespace App\Domains\FAQ\Private\Controllers\Admin;

use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Requests\Admin\FaqCategoryRequest;
use App\Domains\FAQ\Private\Services\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class FaqCategoryController extends Controller
{
    public function __construct(
        private readonly FaqService $faqService,
    ) {}

    public function index(): View
    {
        $categories = FaqCategory::query()
            ->withCount('questions')
            ->orderBy('sort_order')
            ->get();

        return view('faq::pages.admin.faq-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('faq::pages.admin.faq-categories.create');
    }

    public function store(FaqCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['sort_order'] = (FaqCategory::max('sort_order') ?? 0) + 1;

        $this->faqService->createCategory($data);

        return redirect()->route('faq.admin.faq-categories.index')
            ->with('success', __('faq::admin.categories.created'));
    }

    public function edit(FaqCategory $faqCategory): View
    {
        return view('faq::pages.admin.faq-categories.edit', compact('faqCategory'));
    }

    public function update(FaqCategoryRequest $request, FaqCategory $faqCategory): RedirectResponse
    {
        $this->faqService->updateCategory($faqCategory->id, $request->validated());

        return redirect()->route('faq.admin.faq-categories.index')
            ->with('success', __('faq::admin.categories.updated'));
    }

    public function destroy(FaqCategory $faqCategory): RedirectResponse
    {
        $this->faqService->deleteCategory($faqCategory->id);

        return redirect()->route('faq.admin.faq-categories.index')
            ->with('success', __('faq::admin.categories.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:faq_categories,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            FaqCategory::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        $this->faqService->clearCache();

        return response()->json(['success' => true]);
    }

    public function toggleActive(FaqCategory $faqCategory): RedirectResponse
    {
        $this->faqService->updateCategory($faqCategory->id, ['is_active' => !$faqCategory->is_active]);

        return redirect()->route('faq.admin.faq-categories.index')
            ->with('success', __('faq::admin.categories.active_updated'));
    }
}
