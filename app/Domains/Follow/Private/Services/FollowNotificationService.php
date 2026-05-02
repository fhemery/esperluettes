<?php

namespace App\Domains\Follow\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Follow\Private\Notifications\NewFollowerNotification;
use App\Domains\Follow\Private\Notifications\NewStoryNotification;
use App\Domains\Follow\Private\Repositories\FollowRepository;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Contracts\StoryVisibility;

class FollowNotificationService
{
    public function __construct(
        private FollowRepository $repository,
        private NotificationPublicApi $notificationApi,
        private ProfilePublicApi $profileApi,
        private AuthPublicApi $authApi,
    ) {}

    public function notifyNewFollower(int $followerId, int $followedId): void
    {
        $followerProfile = $this->profileApi->getPublicProfile($followerId);
        if ($followerProfile === null) {
            return;
        }

        $notification = new NewFollowerNotification(
            followerId: $followerId,
            followerName: $followerProfile->display_name,
            followerSlug: $followerProfile->slug,
        );

        $this->notificationApi->createNotification(
            userIds: [$followedId],
            content: $notification,
            sourceUserId: $followerId,
        );
    }

    public function notifyFollowersOfNewStory(
        int $authorId,
        string $authorSlug,
        string $authorName,
        int $storyId,
        string $storyTitle,
        string $storySlug,
        string $visibility,
    ): void {
        $followerIds = $this->repository->getFollowerIds($authorId);

        if (empty($followerIds)) {
            return;
        }

        if ($visibility === StoryVisibility::COMMUNITY) {
            $rolesByUserId = $this->authApi->getRolesByUserIds($followerIds);
            $followerIds = array_values(array_filter(
                $followerIds,
                fn(int $id) => collect($rolesByUserId[$id] ?? [])
                    ->contains(fn($role) => $role->slug === Roles::USER_CONFIRMED)
            ));
        }

        if (empty($followerIds)) {
            return;
        }

        $notification = new NewStoryNotification(
            authorId: $authorId,
            authorName: $authorName,
            authorSlug: $authorSlug,
            storyId: $storyId,
            storyTitle: $storyTitle,
            storySlug: $storySlug,
        );

        $this->notificationApi->createNotification(
            userIds: $followerIds,
            content: $notification,
            sourceUserId: $authorId,
        );
    }
}
