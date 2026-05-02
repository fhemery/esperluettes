<?php

namespace App\Domains\Follow\Private\Services;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Follow\Private\Repositories\FollowRepository;
use App\Domains\Follow\Public\Events\UserFollowed;

class FollowService
{
    public function __construct(
        private FollowRepository $repository,
        private FollowNotificationService $notifications,
        private EventBus $eventBus,
    ) {}

    public function follow(int $followerId, int $followedId): void
    {
        $created = $this->repository->follow($followerId, $followedId);

        $this->eventBus->emit(new UserFollowed($followerId, $followedId));
        $this->notifications->notifyNewFollower($followerId, $followedId);
    }

    public function unfollow(int $followerId, int $followedId): void
    {
        $this->repository->unfollow($followerId, $followedId);
    }

    public function isFollowing(int $followerId, int $followedId): bool
    {
        return $this->repository->isFollowing($followerId, $followedId);
    }
}
