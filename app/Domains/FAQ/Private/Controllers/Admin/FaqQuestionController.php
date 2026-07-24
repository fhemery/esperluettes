<?php

namespace App\Domains\FAQ\Private\Controllers\Admin;

use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Private\Requests\Admin\FaqQuestionRequest;
use App\Domains\FAQ\Private\Services\FaqService;
use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class FaqQuestionController extends Controller
{
    private const SCOPE = 'faq';

    public function __construct(
        private readonly FaqService $faqService,
        private readonly MediaPublicApi $media,
    ) {}

    /**
     * Resolve the image_path/image_alt_text from the media-image-field payload.
     * A new upload is stored; otherwise the (possibly reused or kept) path is used;
     * empty means the image was removed. Files are never deleted here — the Media
     * GC reclaims any path no question references anymore.
     *
     * @param array<string,mixed> $data validated request data
     * @return array{image_path:?string, image_alt_text:?string}
     */
    private function resolveImage(Request $request, array $data): array
    {
        $file = $request->file('image.file');
        $path = $data['image']['path'] ?? null;
        $alt = $data['image']['alt'] ?? null;

        $imagePath = $file ? $this->media->store(self::SCOPE, $file) : ($path ?: null);

        return [
            'image_path' => $imagePath,
            'image_alt_text' => $imagePath ? $alt : null,
        ];
    }

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
        $data = array_merge($data, $this->resolveImage($request, $data));
        unset($data['image']);

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
        $data = array_merge($data, $this->resolveImage($request, $data));
        unset($data['image']);

        $data['sort_order'] = $faqQuestion->sort_order;

        $this->faqService->updateQuestion($faqQuestion->id, $data);

        return redirect()->route('faq.admin.faq-questions.index')
            ->with('success', __('faq::admin.questions.updated'));
    }

    public function destroy(FaqQuestion $faqQuestion): RedirectResponse
    {
        // The image file is left to the Media GC once no question references it.
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
