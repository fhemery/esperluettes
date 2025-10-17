<?php

namespace App\Domains\Profile\Private\Support\Moderation;

use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;
use App\Domains\Profile\Private\Models\Profile;

class ProfileSnapshotFormatter implements SnapshotFormatterInterface
{
    public function capture(int $entityId): array
    {
        $profile = Profile::find($entityId);
        if (! $profile) {
            return [];
        }

        return [
            'display_name' => $profile->display_name,
            'bio' => $profile->description,
            'social_networks' => [
                'facebook' => $profile->facebook_url,
                'x' => $profile->x_url,
                'instagram' => $profile->instagram_url,
                'youtube' => $profile->youtube_url,
            ]
        ];
    }

    public function render(array $snapshot): string
    {
        return '<div>' . __('profile::moderation.display_name') . e($snapshot['display_name']) . '</div>'
            . '<div>' . __('profile::moderation.bio') . e($snapshot['bio']) . '</div>'
            . '<div>' . __('profile::moderation.social_networks') . e(json_encode($snapshot['social_networks'])) . '</div>';
    }

    public function getReportedUserId(int $entityId): int
    {
        return $entityId;
    }

    public function getContentUrl(int $entityId): string
    {
        $profile = Profile::find($entityId);
        if (! $profile) {
            return '/';
        }

        // Route model binding will use slug via Profile::getRouteKeyName()
        return route('profile.show', $profile->slug);
    }
}
