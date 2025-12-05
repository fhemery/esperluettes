<?php

namespace App\Domains\ReadList\Private\Listeners;

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\ReadList\Private\Services\ReadListService;
use App\Domains\ReadList\Public\Notifications\ReadListStoryCompletedNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Events\StoryUpdated;

class NotifyReadersOnStoryCompleted
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private StoryPublicApi $stories,
        private ProfilePublicApi $profiles,
        private ReadListService $readListService,
    ) {}

    public function handle(StoryUpdated $event): void
    {
        $beforeComplete = (bool) ($event->before->isComplete ?? false);
        $afterComplete = (bool) ($event->after->isComplete ?? false);

        if ($beforeComplete || !$afterComplete) {
            return;
        }

        $storyId = (int) $event->after->storyId;

        $userIds = $this->readListService->getReadersForStory($storyId);
        if (empty($userIds)) {
            return;
        }

        $userIds = $this->stories->filterUsersWithAccessToStory($userIds, $storyId);
        if (empty($userIds)) {
            return;
        }

        $authorIds = $this->stories->getAuthorIds($storyId);
        $authorId = (int) ($authorIds[0] ?? 0);
        $author = $authorId > 0 ? $this->profiles->getPublicProfile($authorId) : null;
        $authorName = (string) ($author?->display_name ?? '');
        $authorSlug = (string) ($author?->slug ?? '');

        $story = $this->stories->getStory($storyId);
        if (!$story) {
            return;
        }

        $content = new ReadListStoryCompletedNotification(
            authorName: $authorName,
            authorSlug: $authorSlug,
            storyTitle: (string) $story->title,
            storySlug: (string) $story->slug,
        );

        $this->notifications->createNotification($userIds, $content, $authorId > 0 ? $authorId : null);
    }
}
