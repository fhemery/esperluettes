<?php

namespace App\Domains\Story\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\ViewModels\BreadcrumbViewModel;
use App\Domains\Shared\ViewModels\PageViewModel;
use App\Domains\Shared\ViewModels\RefViewModel;
use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Story\Private\Http\Requests\ChapterRequest;
use App\Domains\Story\Private\Http\Requests\ReorderChaptersRequest;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Services\ChapterCreditService;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Services\ReadingProgressService;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Private\ViewModels\ChapterViewModel;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChapterController
{
    public function __construct(
        private ChapterService $service,
        private StoryService $storyService,
        private ReadingProgressService $readingProgress,
        private ProfilePublicApi $profileApi,
        private AuthPublicApi $authApi,
        private ChapterCreditService $chapterCreditService,
        private StoryRefPublicApi $storyRefs,
    ) {
    }

    /**
     * Canonical redirect (US-038): if either story or chapter slug base differs while ids match,
     * redirect once to the combined canonical path, preserving query string. Do this before access checks.
     */
    private function canonicalRedirectIfNeeded(Request $request, Story $story, Chapter $chapter, string $requestedStorySlug, string $requestedChapterSlug): ?RedirectResponse
    {
        $requestStoryId = SlugWithId::extractId($requestedStorySlug);
        $chapterId = SlugWithId::extractId($requestedChapterSlug);

        $storyMatchesId = $requestStoryId !== null && (int) $requestStoryId === (int) $story->id;
        $chapterMatchesId = $chapterId !== null && (int) $chapterId === (int) $chapter->id;
        $storyCanonical = SlugWithId::isCanonical($requestedStorySlug, $story->slug);
        $chapterCanonical = SlugWithId::isCanonical($requestedChapterSlug, $chapter->slug);

        if (($storyMatchesId && !$storyCanonical) || ($chapterMatchesId && !$chapterCanonical)) {
            $target = route('chapters.show', [
                'storySlug' => $story->slug,
                'chapterSlug' => $chapter->slug,
            ]);
            $qs = $request->getQueryString();
            if ($qs) {
                $target .= '?' . $qs;
            }
            return redirect()->to($target, 301);
        }

        return null;
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

    public function show(Request $request, string $storySlug, string $chapterSlug): View|RedirectResponse
    {
        // Load story with ordered chapters (minimal fields) for navigation computation
        $opts = new GetStoryOptions(includeChapters: true, includeAuthors: true);
        $story = $this->storyService->getStory($storySlug, $opts);
        // Load full chapter content separately
        $chapterId = SlugWithId::extractId($chapterSlug);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        if ($redirect = $this->canonicalRedirectIfNeeded($request, $story, $chapter, $storySlug, $chapterSlug)) {
            return $redirect;
        }

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

        // Compute read status via service
        $isReadByMe = false;
        if ($userId !== null && !$isAuthor) {
            $isReadByMe = $this->readingProgress->isChapterReadByUser($userId, (int) $chapter->id);
        }

        // Resolve requested feedback (if any) from Story referentials
        $feedbackVm = null;
        if ($story->story_ref_feedback_id !== null) {
            $feedbacksById = $this->storyRefs->getAllFeedbacks()->keyBy('id');
            $fbDto = $feedbacksById->get($story->story_ref_feedback_id);
            if ($fbDto) {
                $feedbackVm = new RefViewModel((string) $fbDto->name, $fbDto->description);
            }
        }

        // Build ViewModel
        /** @var array<ProfileDto> $authors */
        $authors = $this->profileApi->getPublicProfiles($story->authors->pluck('user_id')->toArray());
        $auth = [];
        foreach ($authors as $author) {
            $auth[] = $author;
        }
        $vm = ChapterViewModel::from($story, $chapter, $isAuthor, $isReadByMe, $auth, $feedbackVm);

        // Build PageViewModel
        $trail = BreadcrumbViewModel::FromHome($user !== null);
        $trail->push(__('shared::navigation.stories'), route('stories.index'));
        $trail->push($story->title, route('stories.show', ['slug' => $story->slug]));
        $trail->push($chapter->title, null, true);

        $page = PageViewModel::make()
            ->withTitle($chapter->title)
            ->withBreadcrumbs($trail);

        // Get audience maturity info for content gate
        $audienceInfo = null;
        if ($story->story_ref_audience_id !== null) {
            $audienceDto = $this->storyRefs->getAudienceById($story->story_ref_audience_id);
            if ($audienceDto && $audienceDto->is_mature_audience && $audienceDto->threshold_age !== null) {
                $audienceInfo = [
                    'is_mature' => true,
                    'threshold_age' => $audienceDto->threshold_age,
                ];
            }
        }

        return view('story::chapters.show', [
            'vm' => $vm,
            'page' => $page,
            'isModerator' => $this->authApi->hasAnyRole([
                Roles::MODERATOR,
                Roles::ADMIN,
                Roles::TECH_ADMIN,
            ]),
            'canCreateChapter' => $isAuthor && $this->chapterCreditService->availableForUser($userId) > 0,
            'audienceInfo' => $audienceInfo,
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

        // Build PageViewModel with breadcrumbs
        $trail = BreadcrumbViewModel::FromHome($request->user() !== null);
        $trail->push(__('shared::navigation.stories'), route('stories.index'));
        $trail->push($story->title, route('stories.show', ['slug' => $story->slug]));
        $trail->push($chapter->title, route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]));
        $trail->push(__('story::chapters.edit.breadcrumb'), null, true);

        $page = PageViewModel::make()
            ->withTitle($chapter->title)
            ->withBreadcrumbs($trail);

        return view('story::chapters.edit', [
            'story' => $story,
            'chapter' => $chapter,
            'page' => $page,
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
            $chapter = $this->service->updateChapter($story, $chapter, $request, (int) Auth::id());
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return back()->withErrors($ve->errors())->withInput();
        }

        return redirect()->route('chapters.show', [
            'storySlug' => $story->slug,
            'chapterSlug' => $chapter->slug,
        ])->with('status', __('story::chapters.updated_success'));
    }

    public function destroy(Request $request, string $storySlug, string $chapterSlug): RedirectResponse
    {
        $storyId = SlugWithId::extractId($storySlug);
        $chapterId = SlugWithId::extractId($chapterSlug);

        $story = Story::query()->findOrFail($storyId);
        $chapter = Chapter::query()->where('story_id', $story->id)->findOrFail($chapterId);

        // Authors/co-authors only; 404 for unauthorized
        if (!Gate::allows('edit', $chapter)) {
            abort(404);
        }

        // Hard delete chapter. Per US-032/043, do NOT recompute story.last_chapter_published_at
        $this->service->deleteChapter($story, $chapter, (int) Auth::id());

        return redirect()->route('stories.show', ['slug' => $story->slug])
            ->with('status', __('story::chapters.deleted_success'));
    }

    public function reorder(ReorderChaptersRequest $request, string $storySlug)
    {
        $storyId = SlugWithId::extractId($storySlug);
        $story = Story::query()->findOrFail($storyId);

        // Authors only (including co-authors); reuse create policy with Story context
        if (!Gate::allows('create', [Chapter::class, $story])) {
            abort(404);
        }

        $orderedIds = array_map('intval', $request->input('ordered_ids', []));

        try {
            $changes = $this->service->reorderChapters($story, $orderedIds, step: 100);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Invalid ordered_ids: must be a permutation of current chapter ids',
            ], 422);
        }

        return response()->json([
            'changes' => $changes,
        ]);
    }
}
