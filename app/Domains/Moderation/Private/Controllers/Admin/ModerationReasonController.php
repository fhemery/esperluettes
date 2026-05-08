<?php

namespace App\Domains\Moderation\Private\Controllers\Admin;

use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Moderation\Private\Requests\Admin\ModerationReasonRequest;
use App\Domains\Moderation\Private\Services\ModerationService;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModerationReasonController extends Controller
{
    public function __construct(
        private readonly ModerationService $service,
        private readonly ModerationRegistry $registry,
    ) {}

    public function index(Request $request): View
    {
        $query = ModerationReason::query()->orderBy('sort_order')->orderBy('id');

        if ($topicKey = $request->get('topic_key')) {
            $query->where('topic_key', $topicKey);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->get('is_active'));
        }

        $reasons = $query->get();
        $topics = $this->registry->getTopics();

        return view('moderation::pages.admin.moderation-reasons.index', compact('reasons', 'topics'));
    }

    public function create(): View
    {
        $topics = $this->registry->getTopics();

        return view('moderation::pages.admin.moderation-reasons.create', compact('topics'));
    }

    public function store(ModerationReasonRequest $request): RedirectResponse
    {
        $this->service->createReason($request->validated());

        return redirect()->route('moderation.admin.moderation-reasons.index')
            ->with('success', __('moderation::admin.reasons.created'));
    }

    public function edit(ModerationReason $moderationReason): View
    {
        $topics = $this->registry->getTopics();

        return view('moderation::pages.admin.moderation-reasons.edit', [
            'reason' => $moderationReason,
            'topics' => $topics,
        ]);
    }

    public function update(ModerationReasonRequest $request, ModerationReason $moderationReason): RedirectResponse
    {
        $this->service->updateReason($moderationReason, $request->validated());

        return redirect()->route('moderation.admin.moderation-reasons.index')
            ->with('success', __('moderation::admin.reasons.updated'));
    }

    public function destroy(ModerationReason $moderationReason): RedirectResponse
    {
        $reportsCount = DB::table('moderation_reports')
            ->where('reason_id', $moderationReason->id)
            ->count();

        if ($reportsCount > 0) {
            return redirect()->route('moderation.admin.moderation-reasons.index')
                ->with('error', __('moderation::admin.reasons.cannot_delete_in_use', ['count' => $reportsCount]));
        }

        $this->service->deleteReason($moderationReason);

        return redirect()->route('moderation.admin.moderation-reasons.index')
            ->with('success', __('moderation::admin.reasons.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:moderation_reasons,id'],
        ]);

        $this->service->reorderReasons($validated['ordered_ids']);

        return response()->json(['success' => true]);
    }
}
