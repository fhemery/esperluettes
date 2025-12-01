<?php

namespace App\Domains\StoryRef\Private\Controllers\Admin;

use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Requests\AudienceRequest;
use App\Domains\StoryRef\Private\Services\AudienceRefService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AudienceController extends Controller
{
    public function __construct(
        private readonly AudienceRefService $audienceService,
    ) {}

    public function index(): View
    {
        $audiences = $this->audienceService->getAll();

        return view('story_ref::pages.admin.audiences.index', [
            'audiences' => $audiences,
        ]);
    }

    public function create(): View
    {
        $nextOrder = StoryRefAudience::query()->max('order') + 1;

        return view('story_ref::pages.admin.audiences.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(AudienceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->audienceService->create($data);

        return redirect()
            ->route('story_ref.admin.audiences.index')
            ->with('success', __('story_ref::admin.audiences.created'));
    }

    public function edit(StoryRefAudience $audience): View
    {
        return view('story_ref::pages.admin.audiences.edit', [
            'audience' => $audience,
        ]);
    }

    public function update(AudienceRequest $request, StoryRefAudience $audience): RedirectResponse
    {
        $data = $request->validated();

        $this->audienceService->update($audience->id, $data);

        return redirect()
            ->route('story_ref.admin.audiences.index')
            ->with('success', __('story_ref::admin.audiences.updated'));
    }

    public function destroy(StoryRefAudience $audience): RedirectResponse
    {
        // Check if audience is in use
        $inUseCount = DB::table('stories')
            ->where('story_ref_audience_id', $audience->id)
            ->count();

        if ($inUseCount > 0) {
            return redirect()
                ->route('story_ref.admin.audiences.index')
                ->with('error', __('story_ref::admin.audiences.cannot_delete_in_use', ['count' => $inUseCount]));
        }

        $this->audienceService->delete($audience->id);

        return redirect()
            ->route('story_ref.admin.audiences.index')
            ->with('success', __('story_ref::admin.audiences.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:story_ref_audiences,id'],
        ]);

        $orderedIds = $validated['ordered_ids'];

        foreach ($orderedIds as $index => $id) {
            StoryRefAudience::where('id', $id)->update(['order' => $index + 1]);
        }

        // Clear cache
        $this->audienceService->clearCache();

        return response()->json(['success' => true]);
    }
}
