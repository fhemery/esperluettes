<?php

namespace App\Domains\ReadList\Private\Services;

use App\Domains\ReadList\Private\Models\ReadListEntry;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\ReadList\Public\Notifications\ReadListAddedNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;

class ReadListService
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private StoryPublicApi $stories,
        private ProfilePublicApi $profiles,
    ) {}

    public function hasStory(int $userId, int $storyId): bool
    {
        return ReadListEntry::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->exists();
    }

    public function addStory(int $userId, int $storyId): bool
    {
        $exists = $this->hasStory($userId, $storyId);
        
        if ($exists) {
            return false;
        }

        ReadListEntry::create([
            'user_id' => $userId,
            'story_id' => $storyId,
        ]);

        $this->notifyAuthorsOnAdd($userId, $storyId);

        return true;
    }

    public function removeStory(int $userId, int $storyId): void
    {
        ReadListEntry::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->delete();
    }

    public function countReadersForStory(int $storyId): int
    {
        return ReadListEntry::where('story_id', $storyId)->count();
    }

    private function notifyAuthorsOnAdd(int $userId, int $storyId): void
    {
        // Robustness: do not notify if user is an author
        if ($this->stories->isAuthor($userId, $storyId)) {
            return;
        }

        // Prepare notification content
        $story = $this->stories->getStory($storyId);
        if (!$story) {
            return;
        }

        $profile = $this->profiles->getPublicProfile($userId);
        $readerName = (string) ($profile?->display_name ?? '');
        $readerSlug = (string) ($profile?->slug ?? '');

        $content = new ReadListAddedNotification(
            readerName: $readerName,
            readerSlug: $readerSlug,
            storyTitle: (string) $story->title,
            storySlug: (string) $story->slug,
        );

        // Notify all authors (excluding reader if somehow co-author)
        $recipients = $this->stories->getAuthorIds($storyId);
        if (!empty($recipients)) {
            $this->notifications->createNotification($recipients, $content, $userId);
        }
    }
}
