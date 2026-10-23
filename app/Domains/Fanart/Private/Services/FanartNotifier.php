<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Public\Notifications\FanartAcceptedNotification;
use App\Domains\Fanart\Public\Notifications\FanartAudioReplacedNotification;
use App\Domains\Fanart\Public\Notifications\FanartDeclinedNotification;
use App\Domains\Fanart\Public\Notifications\FanartDeletedByModerationNotification;
use App\Domains\Fanart\Public\Notifications\FanartDeletedNotification;
use App\Domains\Fanart\Public\Notifications\FanartNotificationContent;
use App\Domains\Fanart\Public\Notifications\FanartRemovedWithContentNotification;
use App\Domains\Fanart\Public\Notifications\FanartRoleDeclinedNotification;
use App\Domains\Fanart\Public\Notifications\FanartRoleGivenNotification;
use App\Domains\Fanart\Public\Notifications\FanartRoleRequestedNotification;
use App\Domains\Fanart\Public\Notifications\FanartSubmittedNotification;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use Illuminate\Validation\ValidationException;

/**
 * The only sender of `fanart.*` notifications: resolves recipients, removes
 * the actor, and snapshots every displayed value into the payload.
 */
class FanartNotifier
{
    public function __construct(
        private readonly StoryPublicApi $storyApi,
        private readonly ProfilePublicApi $profileApi,
        private readonly NotificationPublicApi $notificationApi,
    ) {
    }

    public function fanartSubmitted(Fanart $fanart, int $actorId, ?string $message = null): void
    {
        $this->send(
            $this->storyApi->getAuthorIds($fanart->story_id),
            $actorId,
            fn () => $this->forFanart(FanartSubmittedNotification::class, $fanart, $actorId, $message),
        );
    }

    public function fanartAccepted(Fanart $fanart, int $actorId, ?string $comment): void
    {
        $this->send(
            [$fanart->user_id],
            $actorId,
            fn () => $this->forFanart(FanartAcceptedNotification::class, $fanart, $actorId, $comment),
        );
    }

    public function fanartDeclined(Fanart $fanart, int $actorId, ?string $comment): void
    {
        $this->send(
            [$fanart->user_id],
            $actorId,
            fn () => $this->forFanart(FanartDeclinedNotification::class, $fanart, $actorId, $comment),
        );
    }

    public function fanartDeleted(Fanart $fanart, int $actorId): void
    {
        $this->send(
            [$fanart->user_id],
            $actorId,
            fn () => $this->forFanart(FanartDeletedNotification::class, $fanart, $actorId),
        );
    }

    public function fanartDeletedByModeration(Fanart $fanart, int $moderatorId): void
    {
        $this->send(
            array_merge($this->storyApi->getAuthorIds($fanart->story_id), [$fanart->user_id]),
            $moderatorId,
            fn () => $this->forFanart(FanartDeletedByModerationNotification::class, $fanart, $moderatorId),
        );
    }

    /** @param Fanart $previous the replaced audio; its submitter is told */
    public function fanartAudioReplaced(Fanart $previous, int $actorId): void
    {
        $this->send(
            [$previous->user_id],
            $actorId,
            fn () => $this->forFanart(FanartAudioReplacedNotification::class, $previous, $actorId),
        );
    }

    /**
     * Story and chapter may already be gone, so their titles are passed in.
     */
    public function fanartRemovedWithContent(
        Fanart $fanart,
        string $storyTitle,
        string $storySlug,
        ?string $chapterTitle = null,
        ?int $actorId = null,
    ): void {
        $this->send(
            [$fanart->user_id],
            $actorId,
            fn () => new FanartRemovedWithContentNotification(
                storyId: $fanart->story_id,
                storyTitle: $storyTitle,
                storySlug: $storySlug,
                chapterTitle: (string) $chapterTitle,
                fanartTitle: $fanart->title,
                kind: $fanart->kind->value,
            ),
        );
    }

    public function fanartRoleRequested(int $storyId, int $requesterId): void
    {
        $this->send(
            $this->storyApi->getAuthorIds($storyId),
            $requesterId,
            fn () => $this->forStory(FanartRoleRequestedNotification::class, $storyId, $requesterId),
        );
    }

    public function fanartRoleGiven(int $storyId, int $granteeId, int $actorId): void
    {
        $this->send(
            [$granteeId],
            $actorId,
            fn () => $this->forStory(FanartRoleGivenNotification::class, $storyId, $actorId),
        );
    }

    public function fanartRoleDeclined(int $storyId, int $requesterId, int $actorId): void
    {
        $this->send(
            [$requesterId],
            $actorId,
            fn () => $this->forStory(FanartRoleDeclinedNotification::class, $storyId, $actorId),
        );
    }

    /**
     * @param int[] $recipientIds
     * @param callable(): FanartNotificationContent $content built only when someone will receive it
     */
    private function send(array $recipientIds, ?int $actorId, callable $content): void
    {
        $recipientIds = array_values(array_unique(array_filter(
            array_map('intval', $recipientIds),
            fn (int $id) => $id !== $actorId,
        )));

        if ($recipientIds === []) {
            return;
        }

        try {
            $this->notificationApi->createNotification($recipientIds, $content(), $actorId);
        } catch (ValidationException) {
            // recipients no longer exist: nothing to tell
        }
    }

    /** @param class-string<FanartNotificationContent> $class */
    private function forFanart(string $class, Fanart $fanart, int $actorId, ?string $comment = null): FanartNotificationContent
    {
        $story = $this->storyApi->getStoriesByIds([$fanart->story_id])[$fanart->story_id] ?? null;
        $chapter = $fanart->chapter_id !== null
            ? ($this->storyApi->getChaptersByIds([$fanart->chapter_id])[$fanart->chapter_id] ?? null)
            : null;
        $actor = $this->profileApi->getPublicProfiles([$actorId])[$actorId] ?? null;

        return new $class(
            actorId: $actorId,
            actorName: (string) ($actor?->display_name ?? ''),
            actorSlug: (string) ($actor?->slug ?? ''),
            storyId: $fanart->story_id,
            storyTitle: (string) ($story?->title ?? ''),
            storySlug: (string) ($story?->slug ?? ''),
            chapterTitle: (string) ($chapter?->title ?? ''),
            chapterSlug: (string) ($chapter?->slug ?? ''),
            fanartTitle: $fanart->title,
            kind: $fanart->kind->value,
            comment: $comment,
        );
    }

    /** @param class-string<FanartNotificationContent> $class */
    private function forStory(string $class, int $storyId, int $actorId): FanartNotificationContent
    {
        $story = $this->storyApi->getStoriesByIds([$storyId])[$storyId] ?? null;
        $actor = $this->profileApi->getPublicProfiles([$actorId])[$actorId] ?? null;

        return new $class(
            actorId: $actorId,
            actorName: (string) ($actor?->display_name ?? ''),
            actorSlug: (string) ($actor?->slug ?? ''),
            storyId: $storyId,
            storyTitle: (string) ($story?->title ?? ''),
            storySlug: (string) ($story?->slug ?? ''),
        );
    }
}
