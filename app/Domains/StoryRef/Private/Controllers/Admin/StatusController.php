<?php

namespace App\Domains\StoryRef\Private\Controllers\Admin;

use App\Domains\StoryRef\Private\Models\StoryRefStatus;
use App\Domains\StoryRef\Private\Requests\StatusRequest;
use App\Domains\StoryRef\Private\Services\StatusRefService;
use App\Domains\Administration\Public\Support\ExportCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatusController extends Controller
{
    public function __construct(
        private readonly StatusRefService $statusService,
    ) {}

    public function index(): View
    {
        $statuses = $this->statusService->getAll();

        return view('story_ref::pages.admin.statuses.index', [
            'statuses' => $statuses,
        ]);
    }

    public function create(): View
    {
        $nextOrder = StoryRefStatus::query()->max('order') + 1;

        return view('story_ref::pages.admin.statuses.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(StatusRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->statusService->create($data);

        return redirect()
            ->route('story_ref.admin.statuses.index')
            ->with('success', __('story_ref::admin.statuses.created'));
    }

    public function edit(StoryRefStatus $status): View
    {
        return view('story_ref::pages.admin.statuses.edit', [
            'status' => $status,
        ]);
    }

    public function update(StatusRequest $request, StoryRefStatus $status): RedirectResponse
    {
        $data = $request->validated();

        $this->statusService->update($status->id, $data);

        return redirect()
            ->route('story_ref.admin.statuses.index')
            ->with('success', __('story_ref::admin.statuses.updated'));
    }

    public function destroy(StoryRefStatus $status): RedirectResponse
    {
        $inUseCount = DB::table('stories')
            ->where('story_ref_status_id', $status->id)
            ->count();

        if ($inUseCount > 0) {
            return redirect()
                ->route('story_ref.admin.statuses.index')
                ->with('error', __('story_ref::admin.statuses.cannot_delete_in_use', ['count' => $inUseCount]));
        }

        $this->statusService->delete($status->id);

        return redirect()
            ->route('story_ref.admin.statuses.index')
            ->with('success', __('story_ref::admin.statuses.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:story_ref_statuses,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            StoryRefStatus::where('id', $id)->update(['order' => $index + 1]);
        }

        $this->statusService->clearCache();

        return response()->json(['success' => true]);
    }

    public function export(): StreamedResponse
    {
        $columns = [
            'id' => 'ID',
            'name' => __('story_ref::admin.statuses.table.name'),
            'slug' => __('story_ref::admin.statuses.table.slug'),
            'description' => __('story_ref::admin.statuses.table.description'),
            'is_active' => __('story_ref::admin.statuses.table.active'),
            'order' => __('story_ref::admin.statuses.table.order'),
            'created_at' => __('story_ref::admin.statuses.table.created_at'),
            'updated_at' => __('story_ref::admin.statuses.table.updated_at'),
        ];

        return ExportCsv::streamFromQuery(
            StoryRefStatus::query(),
            $columns,
            'statuses.csv'
        );
    }
}
