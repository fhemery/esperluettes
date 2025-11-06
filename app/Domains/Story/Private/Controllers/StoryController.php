<?php

namespace App\Domains\Story\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\ViewModels\BreadcrumbViewModel;
use App\Domains\Shared\Support\SlugWithId;
use App\Domains\Shared\Support\Seo;
use App\Domains\Shared\ViewModels\PageViewModel;
use App\Domains\Story\Private\Http\Requests\StoryRequest;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Support\StoryFilterAndPagination;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Private\ViewModels\StoryListViewModel;
use App\Domains\Story\Private\ViewModels\StoryShowViewModel;
use App\Domains\Story\Private\ViewModels\ChapterSummaryViewModel;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;
use App\Domains\Story\Private\Services\ChapterCreditService;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Shared\ViewModels\RefViewModel;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Services\ReadingProgressService;
use App\Domains\Story\Private\Services\StoryViewModelBuilder;
use Illuminate\Contracts\View\View;
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
        private readonly CommentPublicApi          $comments,
        private readonly StoryViewModelBuilder     $vmBuilder,
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

        $filter = new StoryFilterAndPagination(page: $page, perPage: 12, visibilities: $vis, typeId: $typeId, audienceIds: $audienceIds, genreIds: $genreIds, excludeTriggerWarningIds: $excludeTwIds, noTwOnly: $noTwOnly);
        $paginator = $this->service->getStories($filter);

        // Referentials lookup for display (types, ...)
        $referentials = $this->lookup->getStoryReferentials();

        $items = $this->vmBuilder->buildStorySummaryItems($paginator->items());

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
        // Build PageViewModel with breadcrumbs: Home/Dashboard > Library > Story (link) > Edit (active)
        $trail = BreadcrumbViewModel::FromHome(Auth::check());
        $trail->push(__('shared::navigation.stories'), route('stories.index'));
        $trail->push($story->title, route('stories.show', ['slug' => $story->slug]));
        $trail->push(trans('story::edit.breadcrumb'), null, true);

        $page = PageViewModel::make()
            ->withTitle($story->title)
            ->withBreadcrumbs($trail);

        return view('story::edit', [
            'story' => $story,
            'referentials' => $referentials,
            'page' => $page,
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
        $items = $this->vmBuilder->buildStorySummaryItems($paginator->items());

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

    public function show(string $slug): View|\Illuminate\Http\RedirectResponse
    {
        // Fetch all the data we need from DB
        $story = $this->service->getStory($slug, GetStoryOptions::Full());
        if (!$story) {
            abort(404);
        }
        $authorUserIds = $story->authors->pluck('user_id')->all();
        $isAuthor = in_array(Auth::id(), $authorUserIds, true);

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
        
        $authors = empty($authorUserIds)
            ? []
            : array_values($this->profileApi->getPublicProfiles($authorUserIds));

        // Resolve type name for display
        $typesById = $this->lookup->getTypes()->keyBy('id');
        $typeArr = $typesById->get($story->story_ref_type_id);
        $typeName = (string) (is_array($typeArr) ? ($typeArr['name'] ?? '') : '');
        $typeDesc = is_array($typeArr) ? ($typeArr['description'] ?? null) : null;
        $typeVm = new RefViewModel($typeName, $typeDesc);

        // Resolve audience name for display
        $audiencesById = $this->lookup->getAudiences()->keyBy('id');
        $audArr = $audiencesById->get($story->story_ref_audience_id);
        $audienceName = (string) (is_array($audArr) ? ($audArr['name'] ?? '') : '');
        $audienceDesc = is_array($audArr) ? ($audArr['description'] ?? null) : null;
        $audienceVm = new RefViewModel($audienceName, $audienceDesc);

        // Resolve copyright name for display
        $copyrightsById = $this->lookup->getCopyrights()->keyBy('id');
        $crArr = $copyrightsById->get($story->story_ref_copyright_id);
        $copyrightName = (string) (is_array($crArr) ? ($crArr['name'] ?? '') : '');
        $copyrightDesc = is_array($crArr) ? ($crArr['description'] ?? null) : null;
        $copyrightVm = new RefViewModel($copyrightName, $copyrightDesc);

        // Resolve status name for display
        $statusesById = $this->lookup->getStatuses()->keyBy('id');
        $stArr = $statusesById->get($story->story_ref_status_id);
        $statusName = is_array($stArr) ? ($stArr['name'] ?? null) : null;
        $statusDesc = is_array($stArr) ? ($stArr['description'] ?? null) : null;
        $statusVm = $statusName !== null ? new RefViewModel((string)$statusName, $statusDesc) : null;

        // Resolve feedback name for display
        $feedbacksById = $this->lookup->getFeedbacks()->keyBy('id');
        $fbArr = $feedbacksById->get($story->story_ref_feedback_id);
        $feedbackName = is_array($fbArr) ? ($fbArr['name'] ?? null) : null;
        $feedbackDesc = is_array($fbArr) ? ($fbArr['description'] ?? null) : null;
        $feedbackVm = $feedbackName !== null ? new RefViewModel((string)$feedbackName, $feedbackDesc) : null;

        // Collect genres as RefViewModel using lookup service (service only loads IDs)
        $genreIds = $story->genres?->pluck('id')->filter()->values()->all() ?? [];
        $genresById = $this->lookup->getGenres()->keyBy('id');
        $genreRefs = [];
        foreach ($genreIds as $gid) {
            $row = $genresById->get($gid);
            if (is_array($row) && isset($row['name'])) {
                $gName = (string)$row['name'];
                $gDesc = $row['description'] ?? null;
                $genreRefs[] = new RefViewModel($gName, is_string($gDesc) ? $gDesc : null);
            }
        }

        // Collect trigger warnings as RefViewModel for display
        $twIds = $story->triggerWarnings?->pluck('id')->filter()->values()->all() ?? [];
        $twById = $this->lookup->getTriggerWarnings()->keyBy('id');
        $triggerWarningRefs = [];
        foreach ($twIds as $tid) {
            $row = $twById->get($tid);
            if (is_array($row) && isset($row['name'])) {
                $twName = (string)$row['name'];
                $twDesc = $row['description'] ?? null;
                $triggerWarningRefs[] = new RefViewModel($twName, is_string($twDesc) ? $twDesc : null);
            }
        }

        // Build chapter metrics from Comment domain in bulk
        $chapterIds = [];
        foreach ($story->chapters as $cRow) { $chapterIds[] = (int)$cRow->id; }
        $rootCounts = $this->comments->getNbRootCommentsFor('chapter', $chapterIds);
        $hasUnreplied = $isAuthor ? $this->comments->hasUnrepliedRootComments('chapter', $chapterIds, $authorUserIds) : [] ;

        $chapters = [];
        foreach ($story->chapters as $c) {
            if (!$isAuthor && $c->status !== Chapter::STATUS_PUBLISHED) {
                continue;
            }
            $cid = (int)$c->id;
            $chapters[] = new ChapterSummaryViewModel(
                id: $cid,
                title: (string)$c->title,
                slug: (string)$c->slug,
                isPublished: (string)$c->status === Chapter::STATUS_PUBLISHED,
                isRead: $c->getIsRead() ?? false,
                readsLogged: (int)($c->reads_logged_count ?? 0),
                wordCount: (int)($c->word_count ?? 0),
                characterCount: (int)($c->character_count ?? 0),
                commentCount: (int)($rootCounts[$cid] ?? 0),
                hasUnrepliedByAuthors: (bool)($hasUnreplied[$cid] ?? false),
                url: route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $c->slug]),
                updatedAt: $c->last_edited_at,
            );
        }

        $viewModel = new StoryShowViewModel(
            $story,
            Auth::id(),
            $authors,
            $chapters,
            $typeVm,
            $audienceVm,
            $copyrightVm,
            $genreRefs,
            $statusVm,
            $feedbackVm,
            $triggerWarningRefs,
            (string) $story->tw_disclosure,
        );

        // Credits state for current user (to drive UI for Add Chapter button)
        $currentUserId = Auth::id() ? (int) Auth::id() : 0;
        $availableChapterCredits = $currentUserId ? $this->credits->availableForUser($currentUserId) : 0;

        $metaDescription = Seo::excerpt($viewModel->getDescription());

        // Build PageViewModel (root with icon, then library link, then active story)
        $trail = BreadcrumbViewModel::FromHome(Auth::check());
        $trail->push(__('shared::navigation.stories'), route('stories.index'));
        $trail->push($viewModel->getTitle(), null, true);

        $page = PageViewModel::make()
            ->withTitle($viewModel->getTitle())
            ->withBreadcrumbs($trail);

        return view('story::show', [
            'viewModel' => $viewModel,
            'metaDescription' => $metaDescription,
            'availableChapterCredits' => $availableChapterCredits,
            'page' => $page,
            'isModerator' => $this->authApi->hasAnyRole([Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN]),
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
