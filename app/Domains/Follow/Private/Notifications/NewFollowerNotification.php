<?php

namespace App\Domains\Follow\Private\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class NewFollowerNotification implements NotificationContent
{
    public function __construct(
        public readonly int $followerId,
        public readonly string $followerName,
        public readonly string $followerSlug,
    ) {}

    public static function type(): string
    {
        return 'follow.new_follower';
    }

    public function toData(): array
    {
        return [
            'follower_id' => $this->followerId,
            'follower_name' => $this->followerName,
            'follower_slug' => $this->followerSlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            followerId: (int) ($data['follower_id'] ?? 0),
            followerName: (string) ($data['follower_name'] ?? ''),
            followerSlug: (string) ($data['follower_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $url = $this->followerSlug !== ''
            ? route('profile.show', ['profile' => $this->followerSlug])
            : '';

        return __('follow::notification.new_follower.display', [
            'follower_name' => $this->followerName,
            'follower_url' => $url,
        ]);
    }
}
