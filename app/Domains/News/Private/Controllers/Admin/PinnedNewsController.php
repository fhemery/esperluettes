<?php

namespace App\Domains\News\Private\Controllers\Admin;

use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PinnedNewsController extends Controller
{
    public function __construct(
        private readonly NewsService $newsService,
    ) {}

    public function index(): View
    {
        $pinnedNews = News::query()
            ->where('is_pinned', true)
            ->orderBy('display_order')
            ->get();

        return view('news::pages.admin.pinned.index', [
            'pinnedNews' => $pinnedNews,
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:news,id'],
        ]);

        $orderedIds = $validated['ordered_ids'];

        foreach ($orderedIds as $index => $id) {
            News::where('id', $id)->update(['display_order' => $index + 1]);
        }

        // Clear carousel cache
        $this->newsService->bustCarouselCache();

        return response()->json(['success' => true]);
    }
}
