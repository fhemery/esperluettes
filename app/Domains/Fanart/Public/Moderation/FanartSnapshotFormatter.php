<?php

namespace App\Domains\Fanart\Public\Moderation;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;

class FanartSnapshotFormatter implements SnapshotFormatterInterface
{
    public function __construct(
        private readonly StoryPublicApi $storyApi,
        private readonly ProfilePublicApi $profileApi,
    ) {
    }

    public function capture(int $entityId): array
    {
        $fanart = Fanart::withTrashed()->find($entityId);
        if (! $fanart) {
            return [];
        }

        $story = $this->storyApi->getStoriesByIds([$fanart->story_id])[$fanart->story_id] ?? null;
        $chapter = $fanart->chapter_id !== null
            ? ($this->storyApi->getChaptersByIds([$fanart->chapter_id])[$fanart->chapter_id] ?? null)
            : null;
        $artist = $this->profileApi->getPublicProfiles([$fanart->user_id])[$fanart->user_id] ?? null;

        return [
            'title' => $fanart->title,
            'description' => $fanart->description,
            'kind' => $fanart->kind->value,
            'status' => $fanart->status->value,
            'artist_id' => $fanart->user_id,
            'artist_name' => (string) ($artist?->display_name ?? ''),
            'story_title' => (string) ($story?->title ?? ''),
            'chapter_title' => (string) ($chapter?->title ?? ''),
            '_media' => [$fanart->file_path],
        ];
    }

    public function render(array $snapshot): string
    {
        return view('fanart::moderation.snapshot', [
            'snapshot' => $snapshot,
            'mediaUrl' => $snapshot['_media_urls'][0] ?? null,
        ])->render();
    }

    public function getReportedUserId(int $entityId): int
    {
        return (int) (Fanart::withTrashed()->whereKey($entityId)->value('user_id') ?? 0);
    }

    public function getContentUrl(int $entityId): string
    {
        $storyId = Fanart::withTrashed()->whereKey($entityId)->value('story_id');
        $story = $storyId !== null ? ($this->storyApi->getStoriesByIds([(int) $storyId])[(int) $storyId] ?? null) : null;
        if (! $story) {
            return '/';
        }

        return '/stories/' . $story->slug . '?tab=fanarts&fanart=' . $entityId;
    }
}
