<?php

namespace App\Domains\StoryRef\Private\Controllers\Admin;

use App\Domains\StoryRef\Private\Models\StoryRefCopyright;
use App\Domains\StoryRef\Private\Requests\CopyrightRequest;
use App\Domains\StoryRef\Private\Services\CopyrightRefService;
use App\Domains\Administration\Public\Support\ExportCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CopyrightController extends Controller
{
    public function __construct(
        private readonly CopyrightRefService $copyrightService,
    ) {}

    public function index(): View
    {
        $copyrights = $this->copyrightService->getAll();

        return view('story_ref::pages.admin.copyrights.index', [
            'copyrights' => $copyrights,
        ]);
    }

    public function create(): View
    {
        $nextOrder = StoryRefCopyright::query()->max('order') + 1;

        return view('story_ref::pages.admin.copyrights.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(CopyrightRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->copyrightService->create($data);

        return redirect()
            ->route('story_ref.admin.copyrights.index')
            ->with('success', __('story_ref::admin.copyrights.created'));
    }

    public function edit(StoryRefCopyright $copyright): View
    {
        return view('story_ref::pages.admin.copyrights.edit', [
            'copyright' => $copyright,
        ]);
    }

    public function update(CopyrightRequest $request, StoryRefCopyright $copyright): RedirectResponse
    {
        $data = $request->validated();

        $this->copyrightService->update($copyright->id, $data);

        return redirect()
            ->route('story_ref.admin.copyrights.index')
            ->with('success', __('story_ref::admin.copyrights.updated'));
    }

    public function destroy(StoryRefCopyright $copyright): RedirectResponse
    {
        // Check if copyright is in use
        $inUseCount = DB::table('stories')
            ->where('story_ref_copyright_id', $copyright->id)
            ->count();

        if ($inUseCount > 0) {
            return redirect()
                ->route('story_ref.admin.copyrights.index')
                ->with('error', __('story_ref::admin.copyrights.cannot_delete_in_use', ['count' => $inUseCount]));
        }

        $this->copyrightService->delete($copyright->id);

        return redirect()
            ->route('story_ref.admin.copyrights.index')
            ->with('success', __('story_ref::admin.copyrights.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:story_ref_copyrights,id'],
        ]);

        $orderedIds = $validated['ordered_ids'];

        foreach ($orderedIds as $index => $id) {
            StoryRefCopyright::where('id', $id)->update(['order' => $index + 1]);
        }

        $this->copyrightService->clearCache();

        return response()->json(['success' => true]);
    }

    public function export(): StreamedResponse
    {
        $columns = [
            'id' => 'ID',
            'name' => __('story_ref::admin.copyrights.table.name'),
            'slug' => __('story_ref::admin.copyrights.table.slug'),
            'description' => __('story_ref::admin.copyrights.table.description'),
            'is_active' => __('story_ref::admin.copyrights.table.active'),
            'order' => __('story_ref::admin.copyrights.table.order'),
            'created_at' => __('story_ref::admin.copyrights.table.created_at'),
            'updated_at' => __('story_ref::admin.copyrights.table.updated_at'),
        ];

        return ExportCsv::streamFromQuery(
            StoryRefCopyright::query(),
            $columns,
            'copyrights.csv'
        );
    }
}
