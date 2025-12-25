<?php

namespace App\Domains\Story\Private\Controllers;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\ViewModels\BreadcrumbViewModel;
use App\Domains\Shared\ViewModels\PageViewModel;
use App\Domains\Story\Private\Services\CollaboratorService;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Support\GetStoryOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollaboratorController
{
    public function __construct(
        private readonly StoryService $storyService,
        private readonly CollaboratorService $collaboratorService,
        private readonly ProfilePublicApi $profileApi,
    ) {}

    /**
     * Display the collaborator management page.
     */
    public function index(string $slug): View
    {
        $story = $this->storyService->getStory($slug, new GetStoryOptions(includeCollaborators: true));

        if (!$story) {
            abort(404);
        }

        $userId = Auth::id();
        if (!$this->collaboratorService->isAuthor($story, $userId)) {
            abort(404);
        }

        $collaborators = $this->collaboratorService->getCollaboratorsWithProfiles($story);
        $authorCount = collect($collaborators)->where('role', CollaboratorService::ROLE_AUTHOR)->count();
        $canLeave = $authorCount > 1;

        $trail = BreadcrumbViewModel::FromHome(true);
        $trail->push(__('story::shared.stories'), route('stories.index'));
        $trail->push($story->title, route('stories.show', ['slug' => $story->slug]));
        $trail->push(__('story::collaborators.breadcrumb'));

        $page = new PageViewModel(
            title: __('story::collaborators.page_title', ['title' => $story->title]),
            breadcrumbs: $trail,
        );

        return view('story::collaborators.index', [
            'page' => $page,
            'story' => $story,
            'collaborators' => $collaborators,
            'currentUserId' => $userId,
            'canLeave' => $canLeave,
            'roles' => CollaboratorService::getAvailableRoles(),
        ]);
    }

    /**
     * Add a collaborator to the story.
     */
    public function store(Request $request, string $slug): RedirectResponse
    {
        $story = $this->storyService->getStory($slug, new GetStoryOptions(includeCollaborators: true));

        if (!$story) {
            abort(404);
        }

        $userId = Auth::id();
        if (!$this->collaboratorService->isAuthor($story, $userId)) {
            abort(404);
        }

        $request->validate([
            'target_users' => 'required|array|min:1',
            'target_users.*' => 'required|integer',
            'role' => 'required|string|in:' . CollaboratorService::ROLE_AUTHOR . ',' . CollaboratorService::ROLE_BETA_READER,
        ]);

        $targetUserIds = $request->input('target_users', []);
        $role = $request->input('role');

        $added = 0;
        $upgraded = 0;
        $errors = [];

        foreach ($targetUserIds as $targetUserId) {
            try {
                $result = $this->collaboratorService->addCollaborator($story, (int) $targetUserId, $role, $userId);
                if ($result === 'added') {
                    $added++;
                } elseif ($result === 'upgraded') {
                    $upgraded++;
                }
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return redirect()
                ->route('stories.collaborators.index', ['slug' => $story->slug])
                ->with('error', implode(' ', $errors));
        }

        $message = '';
        if ($added > 0) {
            $message .= trans_choice('story::collaborators.added', $added, ['count' => $added]);
        }
        if ($upgraded > 0) {
            if ($message) {
                $message .= ' ';
            }
            $message .= trans_choice('story::collaborators.upgraded', $upgraded, ['count' => $upgraded]);
        }

        if (!$message) {
            $message = __('story::collaborators.no_change');
        }

        return redirect()
            ->route('stories.collaborators.index', ['slug' => $story->slug])
            ->with('success', $message);
    }

    /**
     * Remove a collaborator from the story.
     */
    public function destroy(Request $request, string $slug, int $targetUserId): RedirectResponse
    {
        $story = $this->storyService->getStory($slug, new GetStoryOptions(includeCollaborators: true));

        if (!$story) {
            abort(404);
        }

        $userId = Auth::id();
        if (!$this->collaboratorService->isAuthor($story, $userId)) {
            abort(404);
        }

        $removed = $this->collaboratorService->removeCollaborator($story, $targetUserId, $userId);

        if (!$removed) {
            return redirect()
                ->route('stories.collaborators.index', ['slug' => $story->slug])
                ->with('error', __('story::collaborators.cannot_remove'));
        }

        return redirect()
            ->route('stories.collaborators.index', ['slug' => $story->slug])
            ->with('success', __('story::collaborators.removed'));
    }

    /**
     * Leave the story as a collaborator.
     */
    public function leave(string $slug): RedirectResponse
    {
        $story = $this->storyService->getStory($slug, new GetStoryOptions(includeCollaborators: true));

        if (!$story) {
            abort(404);
        }

        $userId = Auth::id();
        $left = $this->collaboratorService->leaveStory($story, $userId);

        if (!$left) {
            return redirect()
                ->route('stories.collaborators.index', ['slug' => $story->slug])
                ->with('error', __('story::collaborators.cannot_leave'));
        }

        return redirect()
            ->route('stories.show', ['slug' => $story->slug])
            ->with('success', __('story::collaborators.left'));
    }
}
