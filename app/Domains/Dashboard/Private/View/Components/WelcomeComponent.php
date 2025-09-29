<?php

namespace App\Domains\Dashboard\Private\View\Components;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View as ViewContract;
use Carbon\Carbon;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Auth;

class WelcomeComponent extends Component
{
    private ProfilePublicApi $profileApi;
    private StoryPublicApi $storyApi;
    private CommentPublicApi $commentApi;

    public ?string $displayName = null;
    public ?string $joinDate = null;
    public ?string $roleLabel = null;
    public ?string $joinDateLabel = null;
    public ?int $storiesCount = null;
    public ?int $commentsCount = null;
    public ?string $error = null;

    public function __construct(
        ProfilePublicApi $profileApi,
        StoryPublicApi $storyApi,
        CommentPublicApi $commentApi
    ) {
        $this->profileApi = $profileApi;
        $this->storyApi = $storyApi;
        $this->commentApi = $commentApi;

        $this->loadData();
    }

    private function loadData(): void
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                $this->error = __('dashboard::welcome.errors.not_authenticated');
                return;
            }

            // Single call to fetch full profile (minimize DB reads)
            $full = $this->profileApi->getFullProfile($userId);
            if ($full === null) {
                $this->error = __('dashboard::welcome.errors.data_unavailable');
                return;
            }

            $this->displayName = $full->displayName;
            // Keep raw ISO date and also compute a localized label in PHP for this component
            $this->joinDate = $full->joinDateIso ?? null;
            if ($this->joinDate) {
                try {
                    $this->joinDateLabel = Carbon::parse($this->joinDate)->locale('fr')->isoFormat('LL');
                } catch (\Throwable $e) {
                    $this->joinDateLabel = $this->joinDate;
                }
            }

            // Role label resolution from roles array
            $userConfirmedRole = array_find($full->roles, fn($r) => $r->slug === Roles::USER_CONFIRMED);
            if ($userConfirmedRole) {
                $this->roleLabel = $userConfirmedRole->name;
            } else {
                $userRole = array_find($full->roles, fn($r) => $r->slug === Roles::USER);
                if ($userRole) {
                    $this->roleLabel = $userRole->name;
                } else {
                    $this->roleLabel = __('dashboard::welcome.role_labels.default');
                }
            }
            $this->storiesCount = $this->storyApi->countAuthoredStories($userId);
            $this->commentsCount = $this->commentApi->countRootCommentsByUser('chapter', $userId);

        } catch (\Throwable $e) {
            $this->error = __('dashboard::welcome.errors.data_unavailable');
        }
    }

    public function render(): ViewContract
    {
        return view('dashboard::components.welcome',[
            'displayName' => $this->displayName,
            'joinDate' => $this->joinDate,
            'roleLabel' => $this->roleLabel,
            'joinDateLabel' => $this->joinDateLabel,
            'storiesCount' => $this->storiesCount,
            'commentsCount' => $this->commentsCount,
            'error' => $this->error,
        ]);
    }
}
