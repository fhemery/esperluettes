<?php

namespace App\Domains\Story\Private\View\Components;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\ChapterCreditService;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Services\StoryViewModelBuilder;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Private\Support\StoryFilterAndPagination;
use App\Domains\Story\Private\ViewModels\StoryListViewModel;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ProfileStoriesComponent extends Component
{
    public StoryListViewModel $viewModel;
    public bool $canEdit;
    public bool $canCreateStory;
    public int $profileUserId;
    public int $availableChapterCredits;
    public bool $showCredits;

    public function __construct(
        private StoryService $service,
        private StoryViewModelBuilder $vmBuilder,
        private AuthPublicApi $authApi,
        private ChapterCreditService $credits,
        int $userId,
    ) {
        $this->profileUserId = $userId;
        $this->hydrate();
    }

    private function hydrate(): void
    {
        $page = (int) request()->query('page', 1);

        // Determine visibilities based on viewer
        if (!Auth::check()) {
            $vis = [Story::VIS_PUBLIC];
        } else {
            $vis = [Story::VIS_PUBLIC, Story::VIS_COMMUNITY, Story::VIS_PRIVATE];
        }

        $isOwner = Auth::id() !== null && Auth::id() === $this->profileUserId;
        $filter = new StoryFilterAndPagination(
            page: $page,
            perPage: 12,
            visibilities: $vis,
            authorIds: [$this->profileUserId],
            requirePublishedChapter: false
        );

        $paginator = $this->service->searchStories($filter, GetStoryOptions::ForCardDisplay());

        $items = $this->vmBuilder->buildStorySummaryItems($paginator->items());

        $this->viewModel = new StoryListViewModel($paginator, $items);

        $this->canEdit = $isOwner;
        $this->canCreateStory = $this->canEdit && $this->authApi->hasAnyRole([Roles::USER_CONFIRMED]);

        $this->availableChapterCredits = $this->credits->availableForUser($this->profileUserId);
        $rolesById = $this->authApi->getRolesByUserIds([$this->profileUserId]);
        $roles = $rolesById[$this->profileUserId] ?? [];
        $this->showCredits = false;
        foreach ($roles as $r) {
            if (isset($r->slug) && (string) $r->slug === (string) Roles::USER_CONFIRMED) {
                $this->showCredits = true;
                break;
            }
        }
    }

    public function render(): ViewContract
    {
        return view('story::components.profile-stories', [
            'viewModel' => $this->viewModel,
            'displayAuthors' => false,
            'canEdit' => $this->canEdit,
            'canCreateStory' => $this->canCreateStory,
            'profileUserId' => $this->profileUserId,
            'availableChapterCredits' => $this->availableChapterCredits,
            'showCredits' => $this->showCredits,
        ]);
    }
}
