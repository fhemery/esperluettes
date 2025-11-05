<?php

namespace App\Domains\ReadList\Private\Services;

use App\Domains\ReadList\Private\Models\ReadListEntry;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\ReadList\Public\Notifications\ReadListAddedNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\ReadList\Public\Events\StoryAddedToReadList;
use App\Domains\ReadList\Public\Events\StoryRemovedFromReadList;

class ReadListService
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private StoryPublicApi $stories,
        private ProfilePublicApi $profiles,
        private EventBus $events,
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

        // Emit domain event
        $this->events->emit(new StoryAddedToReadList(
            userId: $userId,
            storyId: $storyId,
        ));

        return true;
    }

    public function removeStory(int $userId, int $storyId): void
    {
        $deleted = ReadListEntry::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->delete();

        if ($deleted > 0) {
            // Emit domain event only on actual removal
            $this->events->emit(new StoryRemovedFromReadList(
                userId: $userId,
                storyId: $storyId,
            ));
        }
    }

    public function countReadersForStory(int $storyId): int
    {
        return ReadListEntry::where('story_id', $storyId)->count();
    }

    /**
     * Delete all readlist entries for the given story.
     *
     * @return int number of deleted rows
     */
    public function deleteAllForStory(int $storyId): int
    {
        return ReadListEntry::where('story_id', $storyId)->delete();
    }

    /**
     * Delete all readlist entries for the given user.
     *
     * @return int number of deleted rows
     */
    public function deleteAllForUser(int $userId): int
    {
        return ReadListEntry::where('user_id', $userId)->delete();
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

    /**
     * Get all readers for a story
     *
     * @param int $storyId
     * @return array<int>
     */
    public function getReadersForStory(int $storyId): array
    {
        return ReadListEntry::where('story_id', $storyId)
            ->pluck('user_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
    }
}
