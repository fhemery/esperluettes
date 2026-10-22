<?php

namespace App\Domains\Fanart\Private\Listeners;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Services\FanartNotifier;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Events\ChapterDeleted;

class DeleteFanartsOnChapterDeleted
{
    public function __construct(
        private readonly FanartNotifier $notifier,
        private readonly StoryPublicApi $storyApi,
    ) {
    }

    public function handle(ChapterDeleted $event): void
    {
        $chapterId = $event->chapter->id;

        $fanarts = Fanart::withTrashed()->where('chapter_id', $chapterId)->get();
        if ($fanarts->isEmpty()) {
            return;
        }

        Fanart::withTrashed()->where('chapter_id', $chapterId)->forceDelete();

        $story = $this->storyApi->getStoriesByIds([$event->storyId])[$event->storyId] ?? null;
        $authorIds = $this->storyApi->getAuthorIds($event->storyId);

        $fanarts->whereNull('deleted_at')
            ->reject(fn (Fanart $fanart) => in_array($fanart->user_id, $authorIds, true))
            ->unique('user_id')
            ->each(fn (Fanart $fanart) => $this->notifier->fanartRemovedWithContent(
                $fanart,
                (string) ($story?->title ?? ''),
                (string) ($story?->slug ?? ''),
                $event->chapter->title,
            ));
    }
}
