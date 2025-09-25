<?php

namespace App\Domains\Story\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Shared\Support\Seo;
use App\Domains\Story\Private\Http\Requests\StoryRequest;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Support\StoryFilterAndPagination;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Private\ViewModels\StoryListViewModel;
use App\Domains\Story\Private\ViewModels\StoryShowViewModel;
use App\Domains\Story\Private\ViewModels\StorySummaryViewModel;
use App\Domains\Story\Private\ViewModels\ChapterSummaryViewModel;
use App\Domains\Story\Private\Services\ReadingProgressService;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use App\Domains\Story\Private\Services\ChapterCreditService;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoryController
{
    public function __construct(
        private readonly StoryService              $service,
        private readonly AuthPublicApi             $authApi,
        private readonly ProfilePublicApi          $profileApi,
        private readonly StoryRefLookupService     $lookup,
        private readonly ReadingProgressService    $progress,
        private readonly ChapterService            $chapters,
        private readonly ChapterCreditService      $credits,
    )
    {
    }

    public function index(): View
    {
        $page = (int)request()->get('page', 1);
        $typeSlug = request()->get('type');
        $typeId = $this->lookup->findTypeIdBySlug(is_string($typeSlug) ? $typeSlug : null);
        // Audience multi-select: accept audiences[] or comma-separated 'audiences'
        $audiencesParam = request()->get('audiences');
        $audienceSlugs = [];
        if (is_array($audiencesParam)) {
            $audienceSlugs = array_values(array_filter(array_map('strval', $audiencesParam)));
        } elseif (is_string($audiencesParam)) {
            $audienceSlugs = array_values(array_filter(array_map('trim', explode(',', $audiencesParam))));
        }
        // Deduplicate to avoid repeated query params across submissions
        $audienceSlugs = array_values(array_unique($audienceSlugs));
        $audienceIds = $this->lookup->findAudienceIdsBySlugs($audienceSlugs);

        // Genres multi-select (AND semantics): accept genres[] or comma-separated 'genres'
        $genresParam = request()->get('genres');
        $genreSlugs = [];
        if (is_array($genresParam)) {
            $genreSlugs = array_values(array_filter(array_map('strval', $genresParam)));
        } elseif (is_string($genresParam)) {
            $genreSlugs = array_values(array_filter(array_map('trim', explode(',', $genresParam))));
        }
        $genreSlugs = array_values(array_unique($genreSlugs));
        $genreIds = $this->lookup->findGenreIdsBySlugs($genreSlugs);

        // Trigger Warnings exclusion (OR semantics): accept exclude_tw[] or comma-separated 'exclude_tw'
        $twParam = request()->get('exclude_tw');
        $twSlugs = [];
        if (is_array($twParam)) {
            $twSlugs = array_values(array_filter(array_map('strval', $twParam)));
        } elseif (is_string($twParam)) {
            $twSlugs = array_values(array_filter(array_map('trim', explode(',', $twParam))));
        }
        $twSlugs = array_values(array_unique($twSlugs));
        $excludeTwIds = $this->lookup->findTriggerWarningIdsBySlugs($twSlugs);
        $vis = [Story::VIS_PUBLIC];
        if ($this->authApi->hasAnyRole([Roles::USER_CONFIRMED])) {
            $vis[] = Story::VIS_COMMUNITY;
        }
        // Parse "No TW only" checkbox
        $noTwOnly = request()->boolean('no_tw_only', false);

        $filter = new StoryFilterAndPagination(page: $page, perPage: 24, visibilities: $vis, typeId: $typeId, audienceIds: $audienceIds, genreIds: $genreIds, excludeTriggerWarningIds: $excludeTwIds, noTwOnly: $noTwOnly);
        $paginator = $this->service->getStories($filter);

        // Referentials lookup for display (types, ...)
        $referentials = $this->lookup->getStoryReferentials();

        $items = $this->buildStorySummaryItems($paginator);

        $appends = [];
        if ($typeSlug) {
            $appends['type'] = $typeSlug;
        }
        if (!empty($audienceSlugs)) {
            // Use array params so we don't mix CSV and [] formats
            $appends['audiences'] = $audienceSlugs;
        }
        if (!empty($genreSlugs)) {
            $appends['genres'] = $genreSlugs;
        }
        if (!empty($twSlugs)) {
            $appends['exclude_tw'] = $twSlugs;
        }
        if ($noTwOnly) {
            $appends['no_tw_only'] = 1;
        }

        $viewModel = new StoryListViewModel($paginator, $items, $appends);

        return view('story::index', [
            'viewModel' => $viewModel,
            'referentials' => $referentials,
            'currentType' => is_string($typeSlug) ? $typeSlug : null,
            'currentAudiences' => $audienceSlugs,
            'currentGenres' => $genreSlugs,
            'currentExcludeTw' => $twSlugs,
            'currentNoTwOnly' => $noTwOnly,
        ]);
    }

    public function store(StoryRequest $request): RedirectResponse
    {
        $userId = Auth::id();
        $story = $this->service->createStory($request, $userId);

        return redirect()->to('/stories/' . $story->slug)
            ->with('status', __('story::show.created'));
    }

    public function edit(string $slug): View
    {
        $opts = new GetStoryOptions(includeAuthors: true, includeGenreIds: true, includeTriggerWarningIds: true);
        $story = $this->service->getStory($slug, $opts);

        // Author-only: must be a collaborator with role=author
        if (!$story->isAuthor(Auth::id())) {
            abort(404);
        }

        $referentials = $this->lookup->getStoryReferentials();
        return view('story::edit', [
            'story' => $story,
            'referentials' => $referentials,
        ]);
    }

    public function update(StoryRequest $request, string $slug): RedirectResponse
    {
        $opts = new GetStoryOptions(includeAuthors: true, includeGenreIds: true, includeTriggerWarningIds: true);
        $story = $this->service->getStory($slug, $opts);

        // Author-only: must be a collaborator with role=author
        if (!$story->isAuthor(Auth::id())) {
            abort(404);
        }

        $this->service->updateStory($request, $story);

        return redirect()->to('/stories/' . $story->slug)
            ->with('status', __('story::edit.updated'));
    }

    public function profileStories(string $slug): View
    {
        // Resolve profile by slug via public API
        $profile = $this->profileApi->getPublicProfileBySlug($slug);
        if (!$profile) {
            abort(404);
        }
        $userId = (int)$profile->user_id;

        $page = (int)request()->query('page', 1);

        // Determine visibilities based on viewer
        if (!Auth::check()) {
            // Guests: only public
            $vis = [Story::VIS_PUBLIC];
        } else {
            // Authenticated: public + community; service enforces private visibility (owner/collaborator) via viewerId
            $vis = [Story::VIS_PUBLIC, Story::VIS_COMMUNITY, Story::VIS_PRIVATE];
        }

        $isOwner = Auth::id() !== null && Auth::id() === $userId;
        $filter = new StoryFilterAndPagination(
            page: $page,
            perPage: 12,
            visibilities: $vis,
            authorId: $userId,
            requirePublishedChapter: false // profile pages list stories even without published chapters
        );
        $paginator = $this->service->getStories($filter, Auth::id());

        // Build items using shared helper (with authors, genres, TW, and preloaded aggregates)
        $items = $this->buildStorySummaryItems($paginator);

        $viewModel = new StoryListViewModel($paginator, $items);

        $canEdit = $isOwner;
        $canCreateStory = $canEdit && $this->authApi->hasAnyRole([Roles::USER_CONFIRMED]);

        // Compute available chapter credits for this profile user (public info only for confirmed users)
        $availableCredits = $this->credits->availableForUser($userId);
        $rolesById = $this->authApi->getRolesByUserIds([$userId]);
        $roles = $rolesById[$userId] ?? [];
        $profileIsConfirmed = false;
        foreach ($roles as $r) {
            if (isset($r->slug) && (string)$r->slug === (string)Roles::USER_CONFIRMED) {
                $profileIsConfirmed = true;
                break;
            }
        }

        return view('story::partials.profile-stories', [
            'viewModel' => $viewModel,
            'displayAuthors' => false,
            'canEdit' => $canEdit,
            'canCreateStory' => $canCreateStory,
            'profileUserId' => $userId,
            'availableChapterCredits' => $availableCredits,
            'showCredits' => $profileIsConfirmed,
        ]);
    }

    /**
     * Build StorySummaryViewModel items with authors, genres, trigger warnings,
     * and preloaded aggregates from the paginator. Reusable by index and profile listings.
     */
    private function buildStorySummaryItems(LengthAwarePaginator $paginator): array
    {
        // Collect all author user IDs from the page
        $authorIds = $paginator->getCollection()
            ->flatMap(fn($s) => $s->authors->pluck('user_id'))
            ->unique()
            ->values()
            ->all();

        $profilesById = empty($authorIds)
            ? []
            : $this->profileApi->getPublicProfiles($authorIds); // [userId => ProfileDto]

        $items = [];
        $genresById = $this->lookup->getGenres()->keyBy('id');
        $twById = $this->lookup->getTriggerWarnings()->keyBy('id');

        foreach ($paginator->getCollection() as $story) {
            // Map authors to public profile DTOs
            $authorDtos = [];
            foreach ($story->authors as $author) {
                $dto = $profilesById[$author->user_id] ?? null;
                if ($dto) {
                    $authorDtos[] = $dto;
                }
            }

            // Map genre IDs to names for badges
            $gNames = [];
            $ids = $story->genres?->pluck('id')->all() ?? [];
            foreach ($ids as $gid) {
                $row = $genresById->get($gid);
                if (is_array($row) && isset($row['name'])) {
                    $gNames[] = (string)$row['name'];
                }
            }

            // Map trigger warning IDs to names for badges
            $twNames = [];
            $tids = $story->triggerWarnings?->pluck('id')->all() ?? [];
            foreach ($tids as $tid) {
                $row = $twById->get($tid);
                if (is_array($row) && isset($row['name'])) {
                    $twNames[] = (string)$row['name'];
                }
            }

            // Use preloaded aggregates to avoid N+1
            $chaptersCount = (int) ($story->published_chapters_count ?? 0);
            $wordsTotal = (int) ($story->published_words_total ?? 0);

            $items[] = new StorySummaryViewModel(
                id: $story->id,
                title: $story->title,
                slug: $story->slug,
                description: $story->description,
                readsLoggedTotal: (int)($story->reads_logged_total ?? 0),
                chaptersCount: $chaptersCount,
                wordsTotal: $wordsTotal,
                authors: $authorDtos,
                genreNames: $gNames,
                triggerWarningNames: $twNames,
                twDisclosure: (string) $story->tw_disclosure,
            );
        }

        return $items;
    }

    public function show(string $slug): View|\Illuminate\Http\RedirectResponse
    {
        $opts = new GetStoryOptions(includeAuthors: true, includeGenreIds: true, includeTriggerWarningIds: true);
        
        // Fetch all the data we need from DB
        $story = $this->service->getStory($slug, $opts);
        $chapterRows = $this->chapters->getChapters($story, Auth::id());
        $readIds = [];
        if (Auth::check()) {
            $readIds = $this->progress->getReadChapterIdsForUserInStory((int)Auth::id(), (int)$story->id);
        }

        // 301 redirect to canonical slug when base differs but id matches
        if (!SlugWithId::isCanonical($slug, $story->slug)) {
            $reqId = SlugWithId::extractId($slug);
            if ($reqId !== null && $reqId === (int)$story->id) {
                return redirect()->to('/stories/' . $story->slug, 301);
            }
        }

        // Enforce visibility rules via policy
        if (!Gate::allows('view', $story)) {
            abort(404);
        }

        // Fetch authors' public profiles and build ViewModel
        $authorUserIds = $story->authors->pluck('user_id')->all();
        $authors = empty($authorUserIds)
            ? []
            : array_values($this->profileApi->getPublicProfiles($authorUserIds));

        // Resolve type name for display
        $typesById = $this->lookup->getTypes()->keyBy('id');
        $typeArr = $typesById->get($story->story_ref_type_id);
        $typeName = (string) (is_array($typeArr) ? ($typeArr['name'] ?? '') : '');

        // Resolve audience name for display
        $audiencesById = $this->lookup->getAudiences()->keyBy('id');
        $audArr = $audiencesById->get($story->story_ref_audience_id);
        $audienceName = (string) (is_array($audArr) ? ($audArr['name'] ?? '') : '');

        // Resolve copyright name for display
        $copyrightsById = $this->lookup->getCopyrights()->keyBy('id');
        $crArr = $copyrightsById->get($story->story_ref_copyright_id);
        $copyrightName = (string) (is_array($crArr) ? ($crArr['name'] ?? '') : '');

        // Resolve status name for display
        $statusesById = $this->lookup->getStatuses()->keyBy('id');
        $stArr = $statusesById->get($story->story_ref_status_id);
        $statusName = is_array($stArr) ? ($stArr['name'] ?? null) : null;

        // Resolve feedback name for display
        $feedbacksById = $this->lookup->getFeedbacks()->keyBy('id');
        $fbArr = $feedbacksById->get($story->story_ref_feedback_id);
        $feedbackName = is_array($fbArr) ? ($fbArr['name'] ?? null) : null;

        // Collect genre names using lookup service (service only loads IDs)
        $genreIds = $story->genres?->pluck('id')->filter()->values()->all() ?? [];
        $genresById = $this->lookup->getGenres()->keyBy('id');
        $genreNames = [];
        foreach ($genreIds as $gid) {
            $row = $genresById->get($gid);
            if (is_array($row) && isset($row['name'])) {
                $genreNames[] = (string)$row['name'];
            }
        }

        // Collect trigger warning names for display
        $twIds = $story->triggerWarnings?->pluck('id')->filter()->values()->all() ?? [];
        $twById = $this->lookup->getTriggerWarnings()->keyBy('id');
        $triggerWarningNames = [];
        foreach ($twIds as $tid) {
            $row = $twById->get($tid);
            if (is_array($row) && isset($row['name'])) {
                $triggerWarningNames[] = (string)$row['name'];
            }
        }

        $chapters = [];
        foreach ($chapterRows as $c) {
            $chapters[] = new ChapterSummaryViewModel(
                id: (int)$c->id,
                title: (string)$c->title,
                slug: (string)$c->slug,
                isDraft: (string)$c->status !== \App\Domains\Story\Private\Models\Chapter::STATUS_PUBLISHED,
                isRead: in_array((int)$c->id, $readIds, true),
                readsLogged: (int)($c->reads_logged_count ?? 0),
                wordCount: (int)($c->word_count ?? 0),
                characterCount: (int)($c->character_count ?? 0),
                url: route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]),
                updatedAt: $c->last_edited_at,
            );
        }

        $viewModel = new StoryShowViewModel(
            $story,
            Auth::id(),
            $authors,
            $chapters,
            $typeName,
            $audienceName,
            $copyrightName,
            $genreNames,
            $statusName,
            $feedbackName,
            $triggerWarningNames,
            (string) $story->tw_disclosure,
        );

        // Credits state for current user (to drive UI for Add Chapter button)
        $currentUserId = Auth::id() ? (int) Auth::id() : 0;
        $availableChapterCredits = $currentUserId ? $this->credits->availableForUser($currentUserId) : 0;

        $metaDescription = Seo::excerpt($viewModel->getDescription());

        return view('story::show', [
            'viewModel' => $viewModel,
            'metaDescription' => $metaDescription,
            'availableChapterCredits' => $availableChapterCredits,
            ]);
    }

    public function destroy(string $slug): RedirectResponse
    {
        $opts = new GetStoryOptions(includeAuthors: true);
        $story = $this->service->getStory($slug, $opts);

        // Author-only: must be a collaborator with role=author
        if (!$story->isAuthor(Auth::id())) {
            abort(404);
        }

        // Hard delete via service; pivot tables use ON DELETE CASCADE
        $this->service->deleteStory($story);

        return redirect()->route('stories.index')
            ->with('status', __('story::show.deleted'));
    }
}
