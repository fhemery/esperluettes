<?php

namespace App\Domains\StoryRef\Private\Controllers\Admin;

use App\Domains\StoryRef\Private\Models\StoryRefFeedback;
use App\Domains\StoryRef\Private\Requests\FeedbackRequest;
use App\Domains\StoryRef\Private\Services\FeedbackRefService;
use App\Domains\Administration\Public\Support\ExportCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackRefService $feedbackService,
    ) {}

    public function index(): View
    {
        $feedbacks = $this->feedbackService->getAll();

        return view('story_ref::pages.admin.feedbacks.index', [
            'feedbacks' => $feedbacks,
        ]);
    }

    public function create(): View
    {
        $nextOrder = StoryRefFeedback::query()->max('order') + 1;

        return view('story_ref::pages.admin.feedbacks.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(FeedbackRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->feedbackService->create($data);

        return redirect()
            ->route('story_ref.admin.feedbacks.index')
            ->with('success', __('story_ref::admin.feedbacks.created'));
    }

    public function edit(StoryRefFeedback $feedback): View
    {
        return view('story_ref::pages.admin.feedbacks.edit', [
            'feedback' => $feedback,
        ]);
    }

    public function update(FeedbackRequest $request, StoryRefFeedback $feedback): RedirectResponse
    {
        $data = $request->validated();

        $this->feedbackService->update($feedback->id, $data);

        return redirect()
            ->route('story_ref.admin.feedbacks.index')
            ->with('success', __('story_ref::admin.feedbacks.updated'));
    }

    public function destroy(StoryRefFeedback $feedback): RedirectResponse
    {
        // Check if feedback is in use
        $inUseCount = DB::table('stories')
            ->where('story_ref_feedback_id', $feedback->id)
            ->count();

        if ($inUseCount > 0) {
            return redirect()
                ->route('story_ref.admin.feedbacks.index')
                ->with('error', __('story_ref::admin.feedbacks.cannot_delete_in_use', ['count' => $inUseCount]));
        }

        $this->feedbackService->delete($feedback->id);

        return redirect()
            ->route('story_ref.admin.feedbacks.index')
            ->with('success', __('story_ref::admin.feedbacks.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:story_ref_feedbacks,id'],
        ]);

        $orderedIds = $validated['ordered_ids'];

        foreach ($orderedIds as $index => $id) {
            StoryRefFeedback::where('id', $id)->update(['order' => $index + 1]);
        }

        $this->feedbackService->clearCache();

        return response()->json(['success' => true]);
    }

    public function export(): StreamedResponse
    {
        $columns = [
            'id' => 'ID',
            'name' => __('story_ref::admin.feedbacks.table.name'),
            'slug' => __('story_ref::admin.feedbacks.table.slug'),
            'description' => __('story_ref::admin.feedbacks.table.description'),
            'is_active' => __('story_ref::admin.feedbacks.table.active'),
            'order' => __('story_ref::admin.feedbacks.table.order'),
            'created_at' => __('story_ref::admin.feedbacks.table.created_at'),
            'updated_at' => __('story_ref::admin.feedbacks.table.updated_at'),
        ];

        return ExportCsv::streamFromQuery(
            StoryRefFeedback::query(),
            $columns,
            'feedbacks.csv'
        );
    }
}
