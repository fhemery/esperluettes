<?php

namespace App\Domains\Story\Http\Controllers;

use App\Domains\Auth\PublicApi\UserPublicApi;
use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Story\Http\Requests\ChapterRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Services\ChapterService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChapterController
{
    public function __construct(
        private ChapterService $service,
        private UserPublicApi $userPublicApi,
    ) {
    }

    public function create(Request $request, string $storySlug): View
    {
        $storyId = SlugWithId::extractId($storySlug);
        $story = Story::query()->findOrFail($storyId);

        // Authors only; use policy with Story context
        if (!Gate::allows('create', [Chapter::class, $story])) {
            abort(404);
        }

        return view('story::chapters.create', [
            'story' => $story,
        ]);
    }

    public function store(ChapterRequest $request, string $storySlug): RedirectResponse|View
    {
        $storyId = SlugWithId::extractId($storySlug);
        $story = Story::query()->findOrFail($storyId);

        // Authors only; use policy with Story context
        if (!Gate::allows('create', [Chapter::class, $story])) {
            abort(404);
        }

        $userId = (int) $request->user()->id;
        try {
            $chapter = $this->service->createChapter($story, $request, $userId);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return back()->withErrors($ve->errors())->withInput();
        }

        return redirect()->route('chapters.show', [
            'storySlug' => $story->slug,
            'chapterSlug' => $chapter->slug,
        ])->with('status', __('story::chapters.created_success'));
    }

    public function show(Request $request, string $storySlug, string $chapterSlug): View
    {
        $storyId = SlugWithId::extractId($storySlug);
        $chapterId = SlugWithId::extractId($chapterSlug);

        $story = Story::query()->findOrFail($storyId);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        $user = $request->user();
        $userId = $user?->id ? (int) $user->id : null;
        $isAuthor = $userId ? $story->isAuthor($userId) : false;

        if (!Gate::allows('view', $story)) {
            abort(404);
        }

        // Use chapter view policy for unpublished chapters access
        if (!Gate::allows('view', [Chapter::class, $chapter, $story])) {
            abort(404);
        }

        return view('story::chapters.show', [
            'story' => $story,
            'chapter' => $chapter,
        ]);
    }

    public function edit(Request $request, string $storySlug, string $chapterSlug): View
    {
        $storyId = SlugWithId::extractId($storySlug);
        $chapterId = SlugWithId::extractId($chapterSlug);

        $story = Story::query()->findOrFail($storyId);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        if (!Gate::allows('edit', $chapter)) {
            abort(404);
        }

        return view('story::chapters.edit', [
            'story' => $story,
            'chapter' => $chapter,
        ]);
    }

    public function update(ChapterRequest $request, string $storySlug, string $chapterSlug): RedirectResponse
    {
        $storyId = SlugWithId::extractId($storySlug);
        $chapterId = SlugWithId::extractId($chapterSlug);

        $story = Story::query()->findOrFail($storyId);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        if (!Gate::allows('edit', $chapter)) {
            abort(404);
        }

        try {
            $chapter = $this->service->updateChapter($story, $chapter, $request);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return back()->withErrors($ve->errors())->withInput();
        }

        return redirect()->route('chapters.show', [
            'storySlug' => $story->slug,
            'chapterSlug' => $chapter->slug,
        ])->with('status', __('story::chapters.updated_success'));
    }
}
