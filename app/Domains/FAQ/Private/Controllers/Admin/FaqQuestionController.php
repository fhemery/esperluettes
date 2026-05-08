<?php

namespace App\Domains\FAQ\Private\Controllers\Admin;

use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Private\Requests\Admin\FaqQuestionRequest;
use App\Domains\FAQ\Private\Services\FaqService;
use App\Domains\Shared\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class FaqQuestionController extends Controller
{
    public function __construct(
        private readonly FaqService $faqService,
        private readonly ImageService $imageService,
    ) {}

    public function index(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $categories = FaqCategory::query()->orderBy('sort_order')->get(['id', 'name']);

        $query = FaqQuestion::query()->with('category')->orderBy('sort_order');
        if ($categoryId) {
            $query->where('faq_category_id', $categoryId);
        }
        $questions = $query->get();

        return view('faq::pages.admin.faq-questions.index', compact('questions', 'categories', 'categoryId'));
    }

    public function create(): View
    {
        $categories = FaqCategory::query()->orderBy('sort_order')->get(['id', 'name']);

        return view('faq::pages.admin.faq-questions.create', compact('categories'));
    }

    public function store(FaqQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->process(
                'public',
                'faq/' . date('Y/m'),
                $request->file('image'),
                [400, 800]
            );
        }

        $data['sort_order'] = FaqQuestion::where('faq_category_id', $data['faq_category_id'])->count() + 1;

        $this->faqService->createQuestion($data);

        return redirect()->route('faq.admin.faq-questions.index')
            ->with('success', __('faq::admin.questions.created'));
    }

    public function edit(FaqQuestion $faqQuestion): View
    {
        $categories = FaqCategory::query()->orderBy('sort_order')->get(['id', 'name']);

        return view('faq::pages.admin.faq-questions.edit', compact('faqQuestion', 'categories'));
    }

    public function update(FaqQuestionRequest $request, FaqQuestion $faqQuestion): RedirectResponse
    {
        $data = $request->validated();
        $data['image_alt_text'] = $data['image_alt_text'] ?? null;

        if ($request->boolean('image_remove')) {
            if ($faqQuestion->image_path) {
                $this->imageService->deleteWithVariants('public', $faqQuestion->image_path);
            }
            $data['image_path'] = null;
            $data['image_alt_text'] = null;
        } elseif ($request->hasFile('image')) {
            if ($faqQuestion->image_path) {
                $this->imageService->deleteWithVariants('public', $faqQuestion->image_path);
            }
            $data['image_path'] = $this->imageService->process(
                'public',
                'faq/' . date('Y/m'),
                $request->file('image'),
                [400, 800]
            );
        } else {
            $data['image_path'] = $faqQuestion->image_path;
        }

        $data['sort_order'] = $faqQuestion->sort_order;

        $this->faqService->updateQuestion($faqQuestion->id, $data);

        return redirect()->route('faq.admin.faq-questions.index')
            ->with('success', __('faq::admin.questions.updated'));
    }

    public function destroy(FaqQuestion $faqQuestion): RedirectResponse
    {
        if ($faqQuestion->image_path) {
            $this->imageService->deleteWithVariants('public', $faqQuestion->image_path);
        }

        $this->faqService->deleteQuestion($faqQuestion->id);

        return redirect()->route('faq.admin.faq-questions.index')
            ->with('success', __('faq::admin.questions.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:faq_questions,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            FaqQuestion::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        $this->faqService->clearCache();

        return response()->json(['success' => true]);
    }

    public function toggleActive(FaqQuestion $faqQuestion): RedirectResponse
    {
        if ($faqQuestion->is_active) {
            $this->faqService->deactivateQuestion($faqQuestion->id);
        } else {
            $this->faqService->activateQuestion($faqQuestion->id);
        }

        return redirect()->route('faq.admin.faq-questions.index')
            ->with('success', __('faq::admin.questions.active_updated'));
    }
}
