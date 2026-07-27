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
    public bool $isHidden;
    public bool $isOwn;

    public function __construct(
        FollowRepository $repository,
        ProfilePublicApi $profileApi,
        SettingsPublicApi $settings,
        public int $ownerUserId,
    ) {
        $this->isOwn = Auth::id() !== null && (int) Auth::id() === $this->ownerUserId;

        $followingIds = $repository->getFollowingIds($this->ownerUserId);
        $profiles = $followingIds ? $profileApi->getPublicProfiles($followingIds) : [];
        $this->following = array_values($profiles);

        $this->isHidden = (bool) $settings->getValue($this->ownerUserId, 'profile', 'hide-following-tab');
    }

    public function render(): View
    {
        return view('follow::components.following-tab');
    }
}
