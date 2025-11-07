<?php

namespace App\Domains\ReadList\Private\Listeners;

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\ReadList\Private\Services\ReadListService;
use App\Domains\ReadList\Public\Notifications\ReadListStoryRepublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListStoryUnpublishedNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;

class HandleStoryVisibilityChangedForReadList
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private StoryPublicApi $stories,
        private ProfilePublicApi $profiles,
        private ReadListService $readListService,
    ) {}

    public function handle(StoryVisibilityChanged $event): void
    {
        $storyId = (int) $event->storyId;
        $readers = $this->readListService->getReadersForStory($storyId);
        if (empty($readers)) {
            return;
        }

        // Compute diff restricted to readers using explicit visibilities from event
        $diff = $this->stories->diffAccessForUsers($readers, $storyId, (string) $event->oldVisibility);
        $gained = $diff['gained'];
        $lost = $diff['lost'];

        if (empty($gained) && empty($lost)) {
            return;
        }

        // Resolve author and story info
        $authorIds = $this->stories->getAuthorIds($storyId);
        $authorId = (int) ($authorIds[0] ?? 0);
        $author = $authorId > 0 ? $this->profiles->getPublicProfile($authorId) : null;
        $authorName = (string) ($author?->display_name ?? '');
        $authorSlug = (string) ($author?->slug ?? '');

        $story = $this->stories->getStory($storyId);
        $storyTitle = (string) ($story?->title ?? $event->title);
        $storySlug = (string) ($story?->slug ?? '');

        if (!empty($lost)) {
            $content = new ReadListStoryUnpublishedNotification(
                authorName: $authorName,
                authorSlug: $authorSlug,
                storyTitle: $storyTitle,
            );
            $this->notifications->createNotification($lost, $content, $authorId > 0 ? $authorId : null);
        }

        if (!empty($gained)) {
            $content = new ReadListStoryRepublishedNotification(
                authorName: $authorName,
                authorSlug: $authorSlug,
                storyTitle: $storyTitle,
                storySlug: $storySlug,
            );
            $this->notifications->createNotification($gained, $content, $authorId > 0 ? $authorId : null);
        }
    }
}
