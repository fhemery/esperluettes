<?php

namespace App\Domains\StoryRef\Private\Controllers\Admin;

use App\Domains\StoryRef\Private\Models\StoryRefType;
use App\Domains\StoryRef\Private\Requests\TypeRequest;
use App\Domains\StoryRef\Private\Services\TypeRefService;
use App\Domains\Administration\Public\Support\ExportCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TypeController extends Controller
{
    public function __construct(
        private readonly TypeRefService $typeService,
    ) {}

    public function index(): View
    {
        $types = $this->typeService->getAll();

        return view('story_ref::pages.admin.types.index', [
            'types' => $types,
        ]);
    }

    public function create(): View
    {
        $nextOrder = StoryRefType::query()->max('order') + 1;

        return view('story_ref::pages.admin.types.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(TypeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->typeService->create($data);

        return redirect()
            ->route('story_ref.admin.types.index')
            ->with('success', __('story_ref::admin.types.created'));
    }

    public function edit(StoryRefType $type): View
    {
        return view('story_ref::pages.admin.types.edit', [
            'type' => $type,
        ]);
    }

    public function update(TypeRequest $request, StoryRefType $type): RedirectResponse
    {
        $data = $request->validated();

        $this->typeService->update($type->id, $data);

        return redirect()
            ->route('story_ref.admin.types.index')
            ->with('success', __('story_ref::admin.types.updated'));
    }

    public function destroy(StoryRefType $type): RedirectResponse
    {
        $inUseCount = DB::table('stories')
            ->where('story_ref_type_id', $type->id)
            ->count();

        if ($inUseCount > 0) {
            return redirect()
                ->route('story_ref.admin.types.index')
                ->with('error', __('story_ref::admin.types.cannot_delete_in_use', ['count' => $inUseCount]));
        }

        $this->typeService->delete($type->id);

        return redirect()
            ->route('story_ref.admin.types.index')
            ->with('success', __('story_ref::admin.types.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:story_ref_types,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            StoryRefType::where('id', $id)->update(['order' => $index + 1]);
        }

        $this->typeService->clearCache();

        return response()->json(['success' => true]);
    }

    public function export(): StreamedResponse
    {
        $columns = [
            'id' => 'ID',
            'name' => __('story_ref::admin.types.table.name'),
            'slug' => __('story_ref::admin.types.table.slug'),
            'is_active' => __('story_ref::admin.types.table.active'),
            'order' => __('story_ref::admin.types.table.order'),
            'created_at' => __('story_ref::admin.types.table.created_at'),
            'updated_at' => __('story_ref::admin.types.table.updated_at'),
        ];

        return ExportCsv::streamFromQuery(
            StoryRefType::query(),
            $columns,
            'types.csv'
        );
    }
}
