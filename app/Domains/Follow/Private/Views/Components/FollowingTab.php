<?php

namespace App\Domains\Follow\Private\Views\Components;

use App\Domains\Follow\Private\Repositories\FollowRepository;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class FollowingTab extends Component
{
    /** @var ProfileDto[] */
    public array $following;
    public bool $isOwn;
    public bool $isHidden;

    public function __construct(
        public int $userId,
        FollowRepository $repository,
        ProfilePublicApi $profileApi,
        SettingsPublicApi $settings,
    ) {
        $viewerId = Auth::id() !== null ? (int) Auth::id() : null;
        $this->isOwn = $viewerId === $userId;

        $followingIds = $repository->getFollowingIds($userId);
        $profiles = $followingIds ? $profileApi->getPublicProfiles($followingIds) : [];
        $this->following = array_values($profiles);

        $this->isHidden = (bool) $settings->getValue($userId, 'profile', 'hide-following-tab');
    }

    public function render(): View
    {
        return view('follow::components.following-tab');
    }
}
