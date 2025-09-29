<?php

namespace App\Domains\Story\Private\View\Components;

use App\Domains\Auth\Public\Api\Dto\RoleDto;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Services\StoryViewModelBuilder;
use App\Domains\Story\Private\ViewModels\StorySummaryViewModel;
use App\Domains\Story\Private\Services\ChapterCreditService;

class KeepWritingComponent extends Component
{
    public ?StorySummaryViewModel $vm = null;
    public ?string $error = null;
    public bool $isAllowedToCreate = true;
    public bool $hasCreditsLeft = false;

    public function __construct(
        private readonly StoryService $storyService,
        private readonly StoryViewModelBuilder $builder,
        private readonly ChapterCreditService $creditService,
        private readonly ProfilePublicApi $profilePublicApi,
    ) {
        $userId = Auth::id();
        if ($userId === null) {
            $this->error = __('story::keep-writing.errors.not_authenticated');
            return;
        }

        $this->isAllowedToCreate = $this->isConfirmedUser($userId);

        if (!$this->isAllowedToCreate) {
            return;
        }
            
        $story = $this->storyService->getStoryByLatestAddedChapter($userId);
        if ($story) {
            $this->vm = $this->builder->buildStorySummaryItem($story);
            $this->hasCreditsLeft = $this->creditService->availableForUser($userId) > 0;
        }
    }

    public function render(): View
    {
        return view('story::components.keep-writing', [
            'vm' => $this->vm,
            'error' => $this->error,
            'isAllowedToCreate' => $this->isAllowedToCreate,
            'hasCreditsLeft' => $this->hasCreditsLeft,
        ]);
    }

    private function isConfirmedUser(string $userId): bool
    {
        $roles = $this->profilePublicApi->getFullProfile($userId)->roles;
        return array_find($roles, fn(RoleDto $dto) => $dto->slug === Roles::USER_CONFIRMED) !== null;
    }
}
