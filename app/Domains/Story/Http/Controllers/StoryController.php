<?php

namespace App\Domains\Story\Http\Controllers;

use App\Domains\Auth\PublicApi\UserPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Support\Seo;
use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Services\StoryService;
use App\Domains\Story\ViewModels\StoryListViewModel;
use App\Domains\Story\ViewModels\StoryShowViewModel;
use App\Domains\Story\ViewModels\StorySummaryViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoryController
{
    public function __construct(
        private readonly StoryService     $service,
        private readonly UserPublicApi    $userPublicApi,
        private readonly ProfilePublicApi $profileApi
    )
    {
    }

    public function index(): View
    {
        $page = (int)request()->get('page', 1);
        $vis = [Story::VIS_PUBLIC];
        if (Auth::check()) {
            $vis[] = Story::VIS_COMMUNITY;
        }
        $paginator = $this->service->listStories(page: $page, perPage: 24, visibilities: $vis);

        // Collect all author user IDs from the page
        $authorIds = $paginator->getCollection()
            ->flatMap(fn($s) => $s->authors->pluck('user_id'))
            ->unique()
            ->values()
            ->all();

        $profilesById = empty($authorIds)
            ? []
            : $this->profileApi->getPublicProfiles($authorIds); // [userId => ProfileDto]

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

        $viewModel = new StoryListViewModel($paginator, $items);

        return view('story::index', [
            'viewModel' => $viewModel,
        ]);
    }

    public function store(StoryRequest $request): RedirectResponse
    {
        $userId = Auth::id();
        $story = $this->service->createStory($request, $userId);

        return redirect()->to('/stories/' . $story->slug)
            ->with('status', __('Story created successfully.'));
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

        $paginator = $this->service->listStories(
            page: $page,
            perPage: 12,
            visibilities: $vis,
            userId: $userId,
            viewerId: Auth::id(),
        );

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

        return view('story::partials.profile-stories', [
            'viewModel' => $viewModel,
            'displayAuthors' => false,
        ]);
    }

    public function show(string $slug): View|\Illuminate\Http\RedirectResponse
    {
        $story = $this->service->getStoryForShow($slug, Auth::id());

        // Enforce visibility rules
        $user = Auth::user();
        if ($story->visibility === Story::VIS_PRIVATE && !$story->isCollaborator($user->id)) {
            abort(404);
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

        $viewModel = new StoryShowViewModel($story, Auth::id(), $authors);
        $metaDescription = Seo::excerpt($viewModel->getDescription());

        return view('story::show', [
            'viewModel' => $viewModel,
            'metaDescription' => $metaDescription,
        ]);
    }
}
