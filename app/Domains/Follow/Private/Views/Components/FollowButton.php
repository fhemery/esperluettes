<?php

namespace App\Domains\Follow\Private\Views\Components;

use App\Domains\Follow\Private\Repositories\FollowRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class FollowButton extends Component
{
    public bool $isFollowing;
    public bool $canFollow;

    public function __construct(
        public int $userId,
        FollowRepository $repository,
    ) {
        $viewerId = Auth::id();
        $this->canFollow = $viewerId !== null && (int) $viewerId !== $userId;
        $this->isFollowing = $this->canFollow && $repository->isFollowing((int) $viewerId, $userId);
    }

    public function render(): View
    {
        return view('follow::components.follow-button');
    }
}
