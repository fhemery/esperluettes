<?php

namespace App\Domains\Follow\Private\Listeners;

use App\Domains\Follow\Private\Services\FollowNotificationService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Contracts\StoryVisibility;
use App\Domains\Story\Public\Events\StoryCreated;

class StoryCreatedListener
{
    public function __construct(
        private FollowNotificationService $notificationService,
        private ProfilePublicApi $profileApi,
    ) {}

    public function handle(StoryCreated $event): void
    {
        $story = $event->story;

        if (!in_array($story->visibility, [StoryVisibility::PUBLIC, StoryVisibility::COMMUNITY], true)) {
            return;
        }

        $authorProfile = $this->profileApi->getPublicProfile($story->createdByUserId);
        if ($authorProfile === null) {
            return;
        }

        $this->notificationService->notifyFollowersOfNewStory(
            authorId: $story->createdByUserId,
            authorSlug: $authorProfile->slug,
            authorName: $authorProfile->display_name,
            storyId: $story->storyId,
            storyTitle: $story->title,
            storySlug: $story->slug,
            visibility: $story->visibility,
        );
    }
}
