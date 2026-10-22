<?php

namespace App\Domains\Fanart\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * Shared payload of the ten `fanart.*` notifications. Everything shown is
 * denormalised at creation time, so a notification whose story, chapter or
 * fanart was deleted afterwards still renders.
 */
abstract class FanartNotificationContent implements NotificationContent
{
    public function __construct(
        public readonly int $actorId = 0,
        public readonly string $actorName = '',
        public readonly string $actorSlug = '',
        public readonly int $storyId = 0,
        public readonly string $storyTitle = '',
        public readonly string $storySlug = '',
        public readonly string $chapterTitle = '',
        public readonly string $chapterSlug = '',
        public readonly string $fanartTitle = '',
        public readonly string $kind = '',
        public readonly ?string $comment = null,
    ) {
    }

    /** Translation key (under `fanart::notification.`) rendering this type. */
    abstract protected static function langKey(): string;

    public function toData(): array
    {
        return [
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'actor_slug' => $this->actorSlug,
            'story_id' => $this->storyId,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
            'chapter_title' => $this->chapterTitle,
            'chapter_slug' => $this->chapterSlug,
            'fanart_title' => $this->fanartTitle,
            'kind' => $this->kind,
            'comment' => $this->comment,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            actorId: (int) ($data['actor_id'] ?? 0),
            actorName: (string) ($data['actor_name'] ?? ''),
            actorSlug: (string) ($data['actor_slug'] ?? ''),
            storyId: (int) ($data['story_id'] ?? 0),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
            chapterTitle: (string) ($data['chapter_title'] ?? ''),
            chapterSlug: (string) ($data['chapter_slug'] ?? ''),
            fanartTitle: (string) ($data['fanart_title'] ?? ''),
            kind: (string) ($data['kind'] ?? ''),
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
        );
    }

    public function display(): string
    {
        $actorUrl = $this->actorSlug !== ''
            ? route('profile.show', ['profile' => $this->actorSlug])
            : '';
        $storyUrl = $this->storySlug !== ''
            ? route('stories.show', ['slug' => $this->storySlug]) . '?tab=fanarts'
            : '';
        $collaboratorsUrl = $this->storySlug !== ''
            ? route('stories.collaborators.index', ['slug' => $this->storySlug])
            : '';
        $comment = ($this->comment !== null && $this->comment !== '')
            ? ' — « ' . e($this->comment) . ' »'
            : '';

        return __('fanart::notification.' . static::langKey() . '.display', [
            'actor_name' => e($this->actorName),
            'actor_url' => $actorUrl,
            'story_title' => e($this->storyTitle),
            'story_url' => $storyUrl,
            'collaborators_url' => $collaboratorsUrl,
            'chapter_title' => e($this->chapterTitle),
            'fanart_title' => e($this->fanartTitle),
            'kind' => $this->kind !== '' ? __('fanart::notification.kind.' . $this->kind) : '',
            'comment' => $comment,
        ]);
    }
}
