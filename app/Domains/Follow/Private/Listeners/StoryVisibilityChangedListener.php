<?php

namespace App\Domains\Follow\Private\Listeners;

use App\Domains\Follow\Private\Services\FollowNotificationService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StoryVisibility;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;

class StoryVisibilityChangedListener
{
    public function __construct(
        private FollowNotificationService $notificationService,
        private ProfilePublicApi $profileApi,
        private StoryPublicApi $storyApi,
    ) {}

    public function handle(StoryVisibilityChanged $event): void
    {
        $isNowPublishable = in_array($event->newVisibility, [StoryVisibility::PUBLIC, StoryVisibility::COMMUNITY], true);
        $wasPrivate = $event->oldVisibility === StoryVisibility::PRIVATE;

        if (!$isNowPublishable || !$wasPrivate) {
            return;
        }

        $authorIds = $this->storyApi->getAuthorIds($event->storyId);
        if (empty($authorIds)) {
            return;
        }
        $authorId = $authorIds[0];

        $storySummary = $this->storyApi->getStory($event->storyId);
        if ($storySummary === null) {
            return;
        }

        $authorProfile = $this->profileApi->getPublicProfile($authorId);
        if ($authorProfile === null) {
            return;
        }

        $this->notificationService->notifyFollowersOfNewStory(
            authorId: $authorId,
            authorSlug: $authorProfile->slug,
            authorName: $authorProfile->display_name,
            storyId: $event->storyId,
            storyTitle: $event->title,
            storySlug: $storySummary->slug,
            visibility: $event->newVisibility,
        );
    }
}
