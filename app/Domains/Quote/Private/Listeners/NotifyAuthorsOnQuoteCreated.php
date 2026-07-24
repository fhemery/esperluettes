<?php

namespace App\Domains\Quote\Private\Listeners;

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Quote\Public\Events\ChapterPassageQuoted;
use App\Domains\Quote\Public\Notifications\ChapterQuotedNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;

class NotifyAuthorsOnQuoteCreated
{
    public function __construct(
        private readonly StoryPublicApi $storyApi,
        private readonly ProfilePublicApi $profileApi,
        private readonly NotificationPublicApi $notificationApi,
    ) {
    }

    public function handle(ChapterPassageQuoted $event): void
    {
        $authorIds = $this->storyApi->getAuthorIds($event->storyId);
        $authorIds = array_values(array_filter(
            $authorIds,
            fn($id) => $id !== $event->quoterId,
        ));

        if (empty($authorIds)) {
            return;
        }

        $stories = $this->storyApi->getStoriesByIds([$event->storyId]);
        $story = $stories[$event->storyId] ?? null;

        $chapters = $this->storyApi->getChaptersByIds([$event->chapterId]);
        $chapter = $chapters[$event->chapterId] ?? null;

        $quoterProfiles = $this->profileApi->getPublicProfiles([$event->quoterId]);
        $quoterProfile = $quoterProfiles[$event->quoterId] ?? null;

        if ($story === null || $chapter === null || $quoterProfile === null) {
            return;
        }

        $content = new ChapterQuotedNotification(
            quoterId: $event->quoterId,
            quoterName: $quoterProfile->display_name,
            quoterSlug: $quoterProfile->slug,
            chapterId: $event->chapterId,
            chapterTitle: $chapter->title,
            chapterSlug: $chapter->slug,
            storyId: $event->storyId,
            storyTitle: $story->title,
            storySlug: $story->slug,
        );

        try {
            $this->notificationApi->createNotification($authorIds, $content, $event->quoterId);
        } catch (\Illuminate\Validation\ValidationException) {
            // silently skip if authors no longer exist
        }
    }
}
