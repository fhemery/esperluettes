<?php

namespace App\Domains\Story\Http\Controllers;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Services\StoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoryController
{
    public function __construct(private readonly StoryService $service)
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

    public function show(string $slug): View
    {
        [$story, $isAuthor] = $this->service->getStoryForShow($slug, Auth::id());

        return view('story::show', [
            'story' => $story,
            'isAuthor' => $isAuthor,
        ]);
    }
}
