<?php

namespace App\Domains\ReadList\Private\Listeners;

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\ReadList\Private\Services\ReadListService;
use App\Domains\ReadList\Public\Notifications\ReadListChapterPublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListChapterUnpublishedNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterPublished;
use App\Domains\Story\Public\Events\ChapterUnpublished;
use App\Domains\Story\Public\Events\ChapterDeleted;

class NotifyReadersOnChapterModified
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private StoryPublicApi $stories,
        private ProfilePublicApi $profiles,
        private ReadListService $readListService,
    ) {}

    public function onChapterPublished(ChapterPublished $event): void
    {
        $this->notify($event->storyId, $event->chapter->title, $event->chapter->slug);
    }

    public function onChapterCreated(ChapterCreated $event): void
    {
        if (strtolower($event->chapter->status) === 'published') {
            $this->notify($event->storyId, $event->chapter->title, $event->chapter->slug);
        }
    }

    public function onChapterUnpublished(ChapterUnpublished $event): void
    {
        $this->notifyUnpublished($event->storyId, $event->chapter->title, $event->chapter->slug);
    }

    public function onChapterDeleted(ChapterDeleted $event): void
    {
        $this->notifyUnpublished($event->storyId, $event->chapter->title, $event->chapter->slug);
    }

    private function notify(int $storyId, string $chapterTitle, string $chapterSlug): void
    {
        // Find all users who have this story in their read list
        $userIds = $this->readListService->getReadersForStory($storyId);

        if (empty($userIds)) {
            return;
        }

        // Filter by current story access (handles public/private/community)
        $userIds = $this->stories->filterUsersWithAccessToStory($userIds, $storyId);
        if (empty($userIds)) {
            return;
        }

        // Determine author (choose first author for display/source)
        $authorIds = $this->stories->getAuthorIds($storyId);
        $authorId = (int) ($authorIds[0] ?? 0);
        $author = $authorId > 0 ? $this->profiles->getPublicProfile($authorId) : null;
        $authorName = (string) ($author?->display_name ?? '');
        $authorSlug = (string) ($author?->slug ?? '');

        $story = $this->stories->getStory($storyId);
        if (!$story) {
            return;
        }

        $content = new ReadListChapterPublishedNotification(
            authorName: $authorName,
            authorSlug: $authorSlug,
            storyTitle: (string) $story->title,
            storySlug: (string) $story->slug,
            chapterTitle: $chapterTitle,
            chapterSlug: $chapterSlug,
        );

        $this->notifications->createNotification($userIds, $content, $authorId > 0 ? $authorId : null);
    }

    private function notifyUnpublished(int $storyId, string $chapterTitle, string $chapterSlug): void
    {
        // Find all users who have this story in their read list
        $userIds = $this->readListService->getReadersForStory($storyId);
        if (empty($userIds)) {
            return;
        }

        // Filter by current story access (handles public/private/community)
        $userIds = $this->stories->filterUsersWithAccessToStory($userIds, $storyId);
        if (empty($userIds)) {
            return;
        }

        // Determine author (choose first author for display/source)
        $authorIds = $this->stories->getAuthorIds($storyId);
        $authorId = (int) ($authorIds[0] ?? 0);
        $author = $authorId > 0 ? $this->profiles->getPublicProfile($authorId) : null;
        $authorName = (string) ($author?->display_name ?? '');
        $authorSlug = (string) ($author?->slug ?? '');

        $story = $this->stories->getStory($storyId);
        if (!$story) {
            return;
        }

        $content = new ReadListChapterUnpublishedNotification(
            authorName: $authorName,
            authorSlug: $authorSlug,
            storyTitle: (string) $story->title,
            storySlug: (string) $story->slug,
            chapterTitle: $chapterTitle,
            chapterSlug: $chapterSlug,
        );

        $this->notifications->createNotification($userIds, $content, $authorId > 0 ? $authorId : null);
    }
}
