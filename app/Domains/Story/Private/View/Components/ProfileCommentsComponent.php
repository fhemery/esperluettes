<?php

namespace App\Domains\Story\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\ViewModels\StorySummaryViewModel;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

class ProfileCommentsComponent extends Component
{
    /** @var array<int, array{item: StorySummaryViewModel, commentCount: int, storyId: int}> */
    public array $storiesWithCommentCounts = [];
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

        // Step 5: Build view models
        $this->storiesWithCommentCounts = [];
        foreach ($storiesMap as $storyId => $data) {
            $story = $data['story'];
            // Map author user IDs to ProfileDto objects
            $authors = [];
            foreach ($data['authorUserIds'] as $authorUserId) {
                $profile = $profilesByUserId[$authorUserId] ?? null;
                if ($profile !== null) {
                    $authors[] = $profile;
                }
            }

            $this->storiesWithCommentCounts[] = [
                'item' => new StorySummaryViewModel(
                    id: (int) $story->id,
                    title: $story->title,
                    slug: $story->slug,
                    description: $story->description,
                    readsLoggedTotal: 0,
                    chaptersCount: 0,
                    wordsTotal: 0,
                    authors: $authors,
                    genreNames: [],
                    triggerWarningNames: [],
                ),
                'commentCount' => $data['commentCount'],
                'storyId' => $storyId,
            ];
        }

        $this->hasComments = !empty($this->storiesWithCommentCounts);
    }

    public function render(): ViewContract
    {
        return view('story::components.profile-comments', [
            'storiesWithCommentCounts' => $this->storiesWithCommentCounts,
            'hasComments' => $this->hasComments,
            'isAllowed' => $this->isAllowed,
            'profileUserId' => $this->profileUserId,
        ]);
    }
}
