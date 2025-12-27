<?php

namespace App\Domains\Story\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\ViewModels\ProfileCommentsAuthorViewModel;
use App\Domains\Story\Private\ViewModels\ProfileCommentsStoryViewModel;
use Illuminate\Contracts\View\View as ViewContract;
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
        private AuthPublicApi $authApi,
        private ChapterService $chapterService,
        private ProfilePublicApi $profileApi,
        int $userId,
    ) {
        $this->profileUserId = $userId;
        $this->hydrate($userId);
    }

    private function hydrate(int $userId): void
    {
        // Check if the profile user has USER_CONFIRMED role
        $rolesById = $this->authApi->getRolesByUserIds([$userId]);
        $roles = $rolesById[$userId] ?? [];
        $isConfirmed = false;
        foreach ($roles as $r) {
            if (isset($r->slug) && (string) $r->slug === (string) Roles::USER_CONFIRMED) {
                $isConfirmed = true;
                break;
            }
        }

        if (!$isConfirmed) {
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

        // Step 5: Group stories by author (duplicate co-authored stories under each author)
        // Structure: authorUserId => [storyId => storyData]
        $authorStoriesMap = [];
        foreach ($storiesMap as $storyId => $data) {
            foreach ($data['authorUserIds'] as $authorUserId) {
                if (!isset($profilesByUserId[$authorUserId])) {
                    continue;
                }
                if (!isset($authorStoriesMap[$authorUserId])) {
                    $authorStoriesMap[$authorUserId] = [];
                }
                $authorStoriesMap[$authorUserId][$storyId] = $data;
            }
        }

        // Step 6: Build author view models sorted by display_name
        $this->authorGroups = [];
        foreach ($authorStoriesMap as $authorUserId => $stories) {
            $authorProfile = $profilesByUserId[$authorUserId];

            // Build story view models sorted by title
            $storyViewModels = [];
            $totalCommentCount = 0;
            foreach ($stories as $storyId => $storyData) {
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
                author: $authorProfile,
                totalCommentCount: $totalCommentCount,
                stories: $storyViewModels,
            );
        }

        // Sort authors by display_name
        usort($this->authorGroups, fn($a, $b) => strcasecmp($a->author->display_name, $b->author->display_name));

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
