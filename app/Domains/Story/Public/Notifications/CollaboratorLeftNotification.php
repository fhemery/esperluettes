<?php

namespace App\Domains\Story\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class CollaboratorLeftNotification implements NotificationContent
{
    public function __construct(
        public readonly string $userName,
        public readonly string $userSlug,
        public readonly string $storyTitle,
        public readonly string $storySlug,
    ) {}

    public static function type(): string
    {
        return 'story.collaborator.left';
    }

    public function toData(): array
    {
        return [
            'user_name' => $this->userName,
            'user_slug' => $this->userSlug,
            'story_title' => $this->storyTitle,
            'story_slug' => $this->storySlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            userName: (string) ($data['user_name'] ?? ''),
            userSlug: (string) ($data['user_slug'] ?? ''),
            storyTitle: (string) ($data['story_title'] ?? ''),
            storySlug: (string) ($data['story_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $userUrl = $this->userSlug !== '' ? route('profile.show', ['profile' => $this->userSlug]) : '';
        $storyUrl = $this->storySlug !== '' ? route('stories.show', ['slug' => $this->storySlug]) : '';

        return __('story::notification.collaborator.left', [
            'user_name' => $this->userName,
            'user_url' => $userUrl,
            'story_name' => $this->storyTitle,
            'story_url' => $storyUrl,
        ]);
    }
}
