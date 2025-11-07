<?php

namespace App\Domains\ReadList\Private\Controllers;

use App\Domains\ReadList\Private\Services\ReadListService;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Domains\ReadList\Private\ViewModels\ReadListIndexViewModel;
use App\Domains\Story\Public\Contracts\StoryQueryFilterDto;
use App\Domains\Story\Public\Contracts\StoryQueryPaginationDto;
use App\Domains\Story\Public\Contracts\StoryQueryFieldsToReturnDto;

class ReadListController
{
    public function __construct(
        private ReadListService $readListService,
        private StoryPublicApi $storyApi
    ) {
    }

    public function index(): View
    {
        $user = Auth::user();
        $userId = (int) $user->id;

        // Fetch user's readlist story IDs
        $storyIds = $this->readListService->getStoryIdsForUser($userId);

        // If user has no stories in readlist, return empty result
        if (empty($storyIds)) {
            $vm = ReadListIndexViewModel::empty();
            return view('read-list::pages.index', compact('vm'));
        }

        // Build filter & pagination (defaults: page 1, perPage 10)
        $filter = new StoryQueryFilterDto(onlyStoryIds: $storyIds);
        $pagination = new StoryQueryPaginationDto(page: 1, pageSize: 10);
        // We need authors, genre ids and trigger warning ids to build names and links
        $fields = new StoryQueryFieldsToReturnDto(
            includeAuthors: true,
            includeGenreIds: true,
            includeTriggerWarningIds: true,
            includeChapters: true,
            includeReadingProgress: true,
        );

        // Query stories and build view model
        $result = $this->storyApi->listStories($filter, $pagination, $fields);
        $vm = ReadListIndexViewModel::fromPaginated($result);

        return view('read-list::pages.index', compact('vm'));
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

    public function loadMore(): JsonResponse
    {
        $user = Auth::user();
        $userId = (int) $user->id;

        // Get pagination parameters from request
        $page = (int) request('page', 2); // Default to page 2 for load more
        $perPage = (int) request('perPage', 10);

        // Fetch user's readlist story IDs
        $storyIds = $this->readListService->getStoryIdsForUser($userId);

        // If user has no stories in readlist, return empty result
        if (empty($storyIds)) {
            return response()->json([
                'html' => '',
                'hasMore' => false,
                'nextPage' => $page + 1,
                'total' => 0,
            ]);
        }

        // Build filter & pagination
        $filter = new StoryQueryFilterDto(onlyStoryIds: $storyIds);
        $pagination = new StoryQueryPaginationDto(page: $page, pageSize: $perPage);
        
        // Build fields to return
        $fields = new StoryQueryFieldsToReturnDto(
            includeAuthors: true,
            includeGenreIds: true,
            includeTriggerWarningIds: true,
            includeChapters: true,
            includeReadingProgress: true,
        );

        // Query stories and build view model
        $result = $this->storyApi->listStories($filter, $pagination, $fields);
        
        // Convert to view models
        $viewModels = [];
        foreach ($result->data as $storyDto) {
            $viewModels[] = \App\Domains\ReadList\Private\ViewModels\ReadListStoryViewModel::fromDto($storyDto);
        }

        // Return HTML fragment for the stories
        $html = '';
        foreach ($viewModels as $viewModel) {
            $html .= view('read-list::components.read-list-card', ['item' => $viewModel])->render();
        }

        return response()->json([
            'html' => $html,
            'hasMore' => $result->pagination->current_page < $result->pagination->last_page,
            'nextPage' => $result->pagination->current_page + 1,
            'total' => $result->pagination->total,
        ]);
    }
}
