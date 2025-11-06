<?php

namespace App\Domains\ReadList\Private\Controllers;

use App\Domains\ReadList\Private\Services\ReadListService;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ReadListController
{
    public function __construct(
        private ReadListService $readListService,
        private StoryPublicApi $storyApi
    ) {
    }

    public function index(): View
    {
        return view('read-list::pages.index');
    }

    public function add(int $storyId): RedirectResponse
    {
        $user = Auth::user();

        // Check story exists
        $story = $this->storyApi->getStory($storyId);
        if (!$story) {
            abort(404);
        }

        // Check user is not an author (check story_collaborators table)
        $isAuthor = $this->storyApi->isAuthor($user->id, $storyId);

        if ($isAuthor) {
            abort(403);
        }

        // Check user has access to the story
        $allowed = $this->storyApi->filterUsersWithAccessToStory([$user->id], $storyId);
        if (empty($allowed)) {
            abort(403);
        }

        // Add to read list (idempotent)
        $this->readListService->addStory($user->id, $storyId);

        return redirect()->back()->with('success', __('readlist::button.added_message'));
    }

    public function remove(int $storyId): RedirectResponse
    {
        $user = Auth::user();

        // Check story exists
        $story = $this->storyApi->getStory($storyId);
        if (!$story) {
            abort(404);
        }

        // Remove from read list (idempotent)
        $this->readListService->removeStory($user->id, $storyId);

        return redirect()->back()->with('info', __('readlist::button.removed_message'));
    }
}
