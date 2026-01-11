<?php

namespace App\Domains\Story\Private\View\Components;

use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\ViewModels\ProfileCommentsAuthorViewModel;
use App\Domains\Story\Private\ViewModels\ProfileCommentsStoryViewModel;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ProfileCommentsComponent extends Component
{
    /** @var ProfileCommentsAuthorViewModel[] */
    public array $authorGroups = [];
    public bool $hasComments = false;
    public bool $isAllowed = false;
    public int $profileUserId;

    public function __construct(
        private CommentPublicApi $commentApi,
        private ChapterService $chapterService,
        private ProfilePublicApi $profileApi,
        int $userId,
    ) {
        $this->profileUserId = $userId;
        $this->hydrate($userId);
    }

    private function hydrate(int $userId): void
    {
        // Check if comments are viewable using the Profile API
        $viewerUserId = Auth::check() ? Auth::id() : null;
        if (!$this->profileApi->canViewComments($userId, $viewerUserId)) {
            $this->isAllowed = false;
            return;
        }

        $this->isAllowed = true;

        // Step 1: Get all chapter IDs where the user has root comments
        $chapterIds = $this->commentApi->getEntityIdsWithRootCommentsByAuthor('chapter', $userId);

        if (empty($chapterIds)) {
            $this->hasComments = false;
            return;
        }

        // Step 2: Load chapters with their stories (published chapters only, public/community stories only)
        $chapters = $this->chapterService->getPublishedChaptersWithPublicStories($chapterIds);

        if ($chapters->isEmpty()) {
            $this->hasComments = false;
            return;
        }

        // Step 3: Group by story and count comments, collect author user IDs
        $storiesMap = [];
        $authorUserIds = [];
        foreach ($chapters as $chapter) {
            $storyId = (int) $chapter->story_id;
            if (!isset($storiesMap[$storyId])) {
                $story = $chapter->story;
                // Load authors relationship and collect their user IDs
                $storyAuthorIds = $story->authors()->pluck('user_id')->map(fn($id) => (int) $id)->all();
                $storiesMap[$storyId] = [
                    'story' => $story,
                    'commentCount' => 0,
                    'authorUserIds' => $storyAuthorIds,
                ];
                $authorUserIds = array_merge($authorUserIds, $storyAuthorIds);
            }
            $storiesMap[$storyId]['commentCount']++;
        }

        // Step 4: Fetch author profiles in batch
        $profilesByUserId = $this->profileApi->getPublicProfiles(array_unique($authorUserIds));

        // Step 5: Group stories by author set (co-authored stories grouped together)
        // Key: sorted author user IDs joined by dash (e.g., "1-5-12")
        // Value: [authorUserIds => [...], stories => [storyId => storyData]]
        $authorSetStoriesMap = [];
        foreach ($storiesMap as $storyId => $data) {
            // Filter to only authors with valid profiles
            $validAuthorIds = array_filter($data['authorUserIds'], fn($id) => isset($profilesByUserId[$id]));
            if (empty($validAuthorIds)) {
                continue;
            }
            
            // Create a unique key for this author set (sorted IDs)
            $sortedIds = $validAuthorIds;
            sort($sortedIds);
            $authorSetKey = implode('-', $sortedIds);
            
            if (!isset($authorSetStoriesMap[$authorSetKey])) {
                $authorSetStoriesMap[$authorSetKey] = [
                    'authorUserIds' => $validAuthorIds,
                    'stories' => [],
                ];
            }
            $authorSetStoriesMap[$authorSetKey]['stories'][$storyId] = $data;
        }

        // Step 6: Build author group view models
        $this->authorGroups = [];
        foreach ($authorSetStoriesMap as $authorSetKey => $groupData) {
            // Build author profiles array sorted by display_name
            $authorProfiles = [];
            foreach ($groupData['authorUserIds'] as $authorUserId) {
                $authorProfiles[] = $profilesByUserId[$authorUserId];
            }
            usort($authorProfiles, fn($a, $b) => strcasecmp($a->display_name, $b->display_name));

            // Build story view models sorted by title
            $storyViewModels = [];
            $totalCommentCount = 0;
            foreach ($groupData['stories'] as $storyId => $storyData) {
                $story = $storyData['story'];
                $storyViewModels[] = new ProfileCommentsStoryViewModel(
                    id: (int) $story->id,
                    title: $story->title,
                    slug: $story->slug,
                    commentCount: $storyData['commentCount'],
                );
                $totalCommentCount += $storyData['commentCount'];
            }

            // Sort stories by title
            usort($storyViewModels, fn($a, $b) => strcasecmp($a->title, $b->title));

            $this->authorGroups[] = new ProfileCommentsAuthorViewModel(
                authors: $authorProfiles,
                totalCommentCount: $totalCommentCount,
                stories: $storyViewModels,
            );
        }

        // Sort author groups by first author's display_name
        usort($this->authorGroups, fn($a, $b) => strcasecmp($a->authors[0]->display_name, $b->authors[0]->display_name));

        $this->hasComments = !empty($this->authorGroups);
    }

    public function render(): ViewContract
    {
        return view('story::components.profile-comments', [
            'authorGroups' => $this->authorGroups,
            'hasComments' => $this->hasComments,
            'isAllowed' => $this->isAllowed,
            'profileUserId' => $this->profileUserId,
        ]);
    }
}
