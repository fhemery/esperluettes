<?php

namespace App\Domains\Follow\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Follow\Private\Repositories\FollowRepository;

class RemoveFollowsOnUserDeleted
{
    public function __construct(
        private readonly FollowRepository $repository,
    ) {}

    public function handle(UserDeleted $event): void
    {
        $this->repository->deleteAllByFollower($event->userId);
        $this->repository->deleteAllByFollowed($event->userId);
    }
}
