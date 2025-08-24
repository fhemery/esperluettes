<?php

namespace App\Domains\Story\Http\Controllers;

use App\Domains\Auth\PublicApi\UserPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Support\Seo;
use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Services\StoryService;
use App\Domains\Story\Support\StoryFilterAndPagination;
use App\Domains\Story\ViewModels\StoryListViewModel;
use App\Domains\Story\ViewModels\StoryShowViewModel;
use App\Domains\Story\ViewModels\StorySummaryViewModel;
use App\Domains\StoryRef\Services\StoryRefLookupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoryController
{
    public function __construct(
        private readonly StoryService          $service,
        private readonly UserPublicApi         $userPublicApi,
        private readonly ProfilePublicApi      $profileApi,
        private readonly StoryRefLookupService $lookup
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
        $audienceIds = $this->lookup->findAudienceIdsBySlugs($audienceSlugs);
        $vis = [Story::VIS_PUBLIC];
        if (Auth::check()) {
            $vis[] = Story::VIS_COMMUNITY;
        }
        $filter = new StoryFilterAndPagination(page: $page, perPage: 24, visibilities: $vis, typeId: $typeId, audienceIds: $audienceIds);
        $paginator = $this->service->listStories($filter);

        // Collect all author user IDs from the page
        $authorIds = $paginator->getCollection()
            ->flatMap(fn($s) => $s->authors->pluck('user_id'))
            ->unique()
            ->values()
            ->all();

        $profilesById = empty($authorIds)
            ? []
            : $this->profileApi->getPublicProfiles($authorIds); // [userId => ProfileDto]

        // Referentials lookup for display (types, ...)
        $referentials = $this->lookup->getStoryReferentials();

        // Build summaries
        $items = [];
        foreach ($paginator->getCollection() as $story) {
            $authorDtos = [];
            foreach ($story->authors as $author) {
                $dto = $profilesById[$author->user_id] ?? null;
                if ($dto) {
                    $authorDtos[] = $dto;
                }
            }

            $items[] = new StorySummaryViewModel(
                id: $story->id,
                title: $story->title,
                slug: $story->slug,
                description: $story->description,
                authors: $authorDtos,
            );
        }

        $appends = [];
        if ($typeSlug) {
            $appends['type'] = $typeSlug;
        }
        if (!empty($audienceSlugs)) {
            // keep as comma-separated for pagination links
            $appends['audiences'] = implode(',', $audienceSlugs);
        }

        $viewModel = new StoryListViewModel($paginator, $items, $appends);

        return view('story::index', [
            'viewModel' => $viewModel,
            'referentials' => $referentials,
            'currentType' => is_string($typeSlug) ? $typeSlug : null,
            'currentAudiences' => $audienceSlugs,
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
        $story = $this->service->getStoryForShow($slug, Auth::id());

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
        $story = $this->service->getStoryForShow($slug, Auth::id());

        // Author-only: must be a collaborator with role=author
        if (!$story->isAuthor(Auth::id())) {
            abort(404);
        }

        $oldSlug = $story->slug;
        $oldTitle = $story->title;

        $story->title = (string)$request->input('title');
        $story->description = (string)$request->input('description');
        $story->visibility = (string)$request->input('visibility');
        $story->story_ref_type_id = (int)$request->input('story_ref_type_id');
        $story->story_ref_audience_id = (int)$request->input('story_ref_audience_id');
        $story->story_ref_copyright_id = (int)$request->input('story_ref_copyright_id');

        // Sync genres (1..3)
        $genreIds = $request->input('story_ref_genre_ids', []);
        if (is_array($genreIds)) {
            $ids = array_values(array_unique(array_map('intval', $genreIds)));
            $story->genres()->sync($ids);
        }

        // If title changed, regenerate slug base but keep -id suffix
        if ($story->title !== $oldTitle) {
            $slugBase = Story::generateSlugBase($story->title);
            $story->slug = $slugBase . '-' . $story->id;
        }

        $story->save();

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

        $filter = new StoryFilterAndPagination(page: $page, perPage: 12, visibilities: $vis, userId: $userId);
        $paginator = $this->service->listStories($filter, Auth::id());

        // Authors profiles
        $authorIds = $paginator->getCollection()
            ->flatMap(fn($s) => $s->authors->pluck('user_id'))
            ->unique()->values()->all();
        $profilesById = empty($authorIds) ? [] : $this->profileApi->getPublicProfiles($authorIds);

        // Build items
        $items = [];
        foreach ($paginator->getCollection() as $story) {
            $authorDtos = [];
            foreach ($story->authors as $author) {
                $dto = $profilesById[$author->user_id] ?? null;
                if ($dto) {
                    $authorDtos[] = $dto;
                }
            }
            $items[] = new StorySummaryViewModel(
                id: $story->id,
                title: $story->title,
                slug: $story->slug,
                description: $story->description,
                authors: $authorDtos,
            );
        }

        $viewModel = new StoryListViewModel($paginator, $items);

        $canEdit = Auth::id() !== null && Auth::id() === $userId;
        $canCreateStory = $canEdit && Auth::check() && Auth::user()->hasRole('user-confirmed');

        return view('story::partials.profile-stories', [
            'viewModel' => $viewModel,
            'displayAuthors' => false,
            'canEdit' => $canEdit,
            'canCreateStory' => $canCreateStory,
        ]);
    }

    public function show(string $slug): View|\Illuminate\Http\RedirectResponse
    {
        $story = $this->service->getStoryForShow($slug, Auth::id());

        // 301 redirect if slug base changed but id suffix matches
        if ($story->slug !== $slug && preg_match('/-(\d+)$/', $slug, $m)) {
            if ((int)$m[1] === (int)$story->id) {
                return redirect()->to('/stories/' . $story->slug, 301);
            }
        }

        // Enforce visibility rules
        $user = Auth::user();
        if ($story->visibility === Story::VIS_PRIVATE) {
            if (!$user || !$story->isCollaborator($user->id)) {
                abort(404);
            }
        }
        if ($story->visibility === Story::VIS_COMMUNITY) {
            if (!$user) {
                return redirect()->guest(route('login'));
            }
            if (!$this->userPublicApi->isVerified($user)) {
                abort(404);
            }
        }

        // Fetch authors' public profiles and build ViewModel
        $authorUserIds = $story->authors->pluck('user_id')->all();
        $authors = empty($authorUserIds)
            ? []
            : array_values($this->profileApi->getPublicProfiles($authorUserIds));

        // Resolve type name for display
        $typesById = $this->lookup->getTypes()->keyBy('id');
        $typeArr = $typesById->get($story->story_ref_type_id);
        $typeName = is_array($typeArr) ? ($typeArr['name'] ?? null) : null;

        // Resolve audience name for display
        $audiencesById = $this->lookup->getAudiences()->keyBy('id');
        $audArr = $audiencesById->get($story->story_ref_audience_id);
        $audienceName = is_array($audArr) ? ($audArr['name'] ?? null) : null;

        // Resolve copyright name for display
        $copyrightsById = $this->lookup->getCopyrights()->keyBy('id');
        $crArr = $copyrightsById->get($story->story_ref_copyright_id);
        $copyrightName = is_array($crArr) ? ($crArr['name'] ?? null) : null;

        // Collect genre names using lookup service (service only loads IDs)
        $genreIds = $story->genres?->pluck('id')->filter()->values()->all() ?? [];
        $genresById = $this->lookup->getGenres()->keyBy('id');
        $genreNames = [];
        foreach ($genreIds as $gid) {
            $row = $genresById->get($gid);
            if (is_array($row) && isset($row['name'])) {
                $genreNames[] = (string) $row['name'];
            }
        }

        $viewModel = new StoryShowViewModel(
            $story,
            Auth::id(),
            $authors,
            $typeName,
            $audienceName,
            $copyrightName,
            $genreNames,
        );
        $metaDescription = Seo::excerpt($viewModel->getDescription());

        return view('story::show', [
            'viewModel' => $viewModel,
            'metaDescription' => $metaDescription,
        ]);
    }
}
