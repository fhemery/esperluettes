<?php

namespace App\Domains\Follow\Private\Views\Components;

use App\Domains\Follow\Private\Repositories\FollowRepository;
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

    public function __construct(
        FollowRepository $repository,
        ProfilePublicApi $profileApi,
        public int $ownerUserId,
    ) {
        $this->isOwn = Auth::id() !== null && (int) Auth::id() === $this->ownerUserId;

        $followingIds = $repository->getFollowingIds($this->ownerUserId);
        $profiles = $followingIds ? $profileApi->getPublicProfiles($followingIds) : [];
        $this->following = array_values($profiles);
    }

    public function render(): View
    {
        return view('follow::components.following-tab');
    }
}
