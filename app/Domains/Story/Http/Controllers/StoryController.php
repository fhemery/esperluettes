<?php

namespace App\Domains\Story\Http\Controllers;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Services\StoryService;
use App\Domains\Shared\Support\Seo;
use App\Domains\Auth\PublicApi\UserPublicApi;
use Illuminate\Contracts\View\View; 
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoryController
{
    public function __construct(private readonly StoryService $service,
    private readonly UserPublicApi $userPublicApi)
    {
    }

    public function index(): View
    {
        $page = (int) request()->get('page', 1);
        $stories = $this->service->listPublicStories($page, 24);

        return view('story::index', [
            'stories' => $stories,
        ]);
    }

    public function store(StoryRequest $request): RedirectResponse
    {
        $userId = Auth::id();
        $story = $this->service->createStory($request, $userId);

        return redirect()->to('/stories/' . $story->slug)
            ->with('status', __('Story created successfully.'));
    }

    public function show(string $slug): View|\Illuminate\Http\RedirectResponse
    {
        [$story, $isAuthor] = $this->service->getStoryForShow($slug, Auth::id());
        $metaDescription = Seo::excerpt($story->description);

        // Enforce visibility rules
        $user = Auth::user();
        if ($story->visibility === Story::VIS_PRIVATE && ! $isAuthor) {
            abort(404);
        }
        if ($story->visibility === Story::VIS_COMMUNITY) {
            if (! $user) {
                return redirect()->guest(route('login'));
            }
            if (! $this->userPublicApi->isVerified($user)) {
                abort(404);
            }
        }

        return view('story::show', [
            'story' => $story,
            'isAuthor' => $isAuthor,
            'metaDescription' => $metaDescription,
        ]);
    }
}
