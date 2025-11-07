<?php

namespace App\Domains\ReadList\Private\Listeners;

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\ReadList\Private\Services\ReadListService;
use App\Domains\ReadList\Public\Notifications\ReadListStoryDeletedNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Events\StoryDeleted;

class HandleStoryDeletedForReadList
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private StoryPublicApi $stories,
        private ProfilePublicApi $profiles,
        private ReadListService $readListService,
    ) {}

    public function handle(StoryDeleted $event): void
    {
        $storyId = (int) $event->story->storyId;
        $storyTitle = (string) $event->story->title;

        // Find all users who have this story in their read list
        $userIds = $this->readListService->getReadersForStory($storyId);
        if (empty($userIds)) {
            return;
        }

        // We cannot check story access. Anyway, better warn everyone that story is not coming back.

        // Determine author from snapshot creator id
        $authorId = (int) $event->story->createdByUserId;
        $author = $authorId > 0 ? $this->profiles->getPublicProfile($authorId) : null;
        $authorName = (string) ($author?->display_name ?? '');
        $authorSlug = (string) ($author?->slug ?? '');

        $content = new ReadListStoryDeletedNotification(
            authorName: $authorName,
            authorSlug: $authorSlug,
            storyTitle: $storyTitle,
        );

        $this->notifications->createNotification($userIds, $content, $author?->user_id);

        // Cleanup: delete all ReadList entries for this story
        $this->readListService->deleteAllForStory($storyId);
    }
}
