<?php

namespace App\Domains\StoryRef\Private\Controllers\Admin;

use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Requests\GenreRequest;
use App\Domains\StoryRef\Private\Services\GenreRefService;
use App\Domains\Administration\Public\Support\ExportCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenreController extends Controller
{
    public function __construct(
        private readonly GenreRefService $genreService,
    ) {}

    public function index(): View
    {
        $genres = $this->genreService->getAll();

        return view('story_ref::pages.admin.genres.index', [
            'genres' => $genres,
        ]);
    }

    public function create(): View
    {
        $nextOrder = StoryRefGenre::query()->max('order') + 1;

        return view('story_ref::pages.admin.genres.create', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(GenreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->genreService->create($data);

        return redirect()
            ->route('story_ref.admin.genres.index')
            ->with('success', __('story_ref::admin.genres.created'));
    }

    public function edit(StoryRefGenre $genre): View
    {
        return view('story_ref::pages.admin.genres.edit', [
            'genre' => $genre,
        ]);
    }

    public function update(GenreRequest $request, StoryRefGenre $genre): RedirectResponse
    {
        $data = $request->validated();

        $this->genreService->update($genre->id, $data);

        return redirect()
            ->route('story_ref.admin.genres.index')
            ->with('success', __('story_ref::admin.genres.updated'));
    }

    public function destroy(StoryRefGenre $genre): RedirectResponse
    {
        // Check if genre is in use via pivot table
        $inUseCount = DB::table('story_genres')
            ->where('story_ref_genre_id', $genre->id)
            ->count();

        if ($inUseCount > 0) {
            return redirect()
                ->route('story_ref.admin.genres.index')
                ->with('error', __('story_ref::admin.genres.cannot_delete_in_use', ['count' => $inUseCount]));
        }

        $this->genreService->delete($genre->id);

        return redirect()
            ->route('story_ref.admin.genres.index')
            ->with('success', __('story_ref::admin.genres.deleted'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:story_ref_genres,id'],
        ]);

        $orderedIds = $validated['ordered_ids'];

        foreach ($orderedIds as $index => $id) {
            StoryRefGenre::where('id', $id)->update(['order' => $index + 1]);
        }

        $this->genreService->clearCache();

        return response()->json(['success' => true]);
    }

    public function export(): StreamedResponse
    {
        $columns = [
            'id' => 'ID',
            'name' => __('story_ref::admin.genres.table.name'),
            'slug' => __('story_ref::admin.genres.table.slug'),
            'description' => __('story_ref::admin.genres.table.description'),
            'is_active' => __('story_ref::admin.genres.table.active'),
            'order' => __('story_ref::admin.genres.table.order'),
            'created_at' => __('story_ref::admin.genres.table.created_at'),
            'updated_at' => __('story_ref::admin.genres.table.updated_at'),
        ];

        return ExportCsv::streamFromQuery(
            StoryRefGenre::query(),
            $columns,
            'genres.csv'
        );
    }
}
