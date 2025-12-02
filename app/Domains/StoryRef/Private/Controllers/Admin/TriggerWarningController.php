<?php

namespace App\Domains\StoryRef\Private\Controllers\Admin;

use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;
use App\Domains\StoryRef\Private\Requests\TriggerWarningRequest;
use App\Domains\StoryRef\Private\Services\TriggerWarningRefService;
use App\Domains\Administration\Public\Support\ExportCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TriggerWarningController extends Controller
{
    public function __construct(
        private readonly TriggerWarningRefService $triggerWarningService,
    ) {}

    public function index(): View
    {
        $triggerWarnings = $this->triggerWarningService->getAll();

        return view('story_ref::pages.admin.trigger-warnings.index', [
            'triggerWarnings' => $triggerWarnings,
        ]);
    }

    public function create(): View
    {
        $nextOrder = StoryRefTriggerWarning::query()->max('order') + 1;

        return view('story_ref::pages.admin.trigger-warnings.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(TriggerWarningRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->triggerWarningService->create($data);

        return redirect()
            ->route('story_ref.admin.trigger-warnings.index')
            ->with('success', __('story_ref::admin.trigger_warnings.created'));
    }

    public function edit(StoryRefTriggerWarning $trigger_warning): View
    {
        return view('story_ref::pages.admin.trigger-warnings.edit', [
            'triggerWarning' => $trigger_warning,
        ]);
    }

    public function update(TriggerWarningRequest $request, StoryRefTriggerWarning $trigger_warning): RedirectResponse
    {
        $data = $request->validated();

        $this->triggerWarningService->update($trigger_warning->id, $data);

        return redirect()
            ->route('story_ref.admin.trigger-warnings.index')
            ->with('success', __('story_ref::admin.trigger_warnings.updated'));
    }

    public function destroy(StoryRefTriggerWarning $trigger_warning): RedirectResponse
    {
        $inUseCount = DB::table('story_trigger_warnings')
            ->where('story_ref_trigger_warning_id', $trigger_warning->id)
            ->count();

        if ($inUseCount > 0) {
            return redirect()
                ->route('story_ref.admin.trigger-warnings.index')
                ->with('error', __('story_ref::admin.trigger_warnings.cannot_delete_in_use', ['count' => $inUseCount]));
        }

        $this->triggerWarningService->delete($trigger_warning->id);

        return redirect()
            ->route('story_ref.admin.trigger-warnings.index')
            ->with('success', __('story_ref::admin.trigger_warnings.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:story_ref_trigger_warnings,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            StoryRefTriggerWarning::where('id', $id)->update(['order' => $index + 1]);
        }

        $this->triggerWarningService->clearCache();

        return response()->json(['success' => true]);
    }

    public function export(): StreamedResponse
    {
        $columns = [
            'id' => 'ID',
            'name' => __('story_ref::admin.trigger_warnings.table.name'),
            'slug' => __('story_ref::admin.trigger_warnings.table.slug'),
            'description' => __('story_ref::admin.trigger_warnings.table.description'),
            'is_active' => __('story_ref::admin.trigger_warnings.table.active'),
            'order' => __('story_ref::admin.trigger_warnings.table.order'),
            'created_at' => __('story_ref::admin.trigger_warnings.table.created_at'),
            'updated_at' => __('story_ref::admin.trigger_warnings.table.updated_at'),
        ];

        return ExportCsv::streamFromQuery(
            StoryRefTriggerWarning::query(),
            $columns,
            'trigger-warnings.csv'
        );
    }
}
