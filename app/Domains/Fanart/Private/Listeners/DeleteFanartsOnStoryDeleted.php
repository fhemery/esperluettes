<?php

namespace App\Domains\Fanart\Private\Listeners;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\Fanartist;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;
use App\Domains\Fanart\Private\Services\FanartNotifier;
use App\Domains\Story\Public\Events\StoryDeleted;

class DeleteFanartsOnStoryDeleted
{
    public function __construct(private readonly FanartNotifier $notifier)
    {
    }

    public function handle(StoryDeleted $event): void
    {
        $storyId = $event->story->storyId;

        // Soft-deleted rows (deactivated fanartists) go too; their owners are not told.
        $fanarts = Fanart::withTrashed()->where('story_id', $storyId)->get();

        Fanart::withTrashed()->where('story_id', $storyId)->forceDelete();
        Fanartist::where('story_id', $storyId)->delete();
        FanartRoleRequest::where('story_id', $storyId)->delete();

        // The authors' collaborator rows are already gone, so authors are told apart by
        // their artworks: only non-artwork submitters are fanartists to notify.
        $authorIds = $fanarts->where('is_artwork', true)->pluck('user_id')->all();

        $fanarts->whereNull('deleted_at')
            ->reject(fn (Fanart $fanart) => in_array($fanart->user_id, $authorIds, true))
            ->unique('user_id')
            ->each(fn (Fanart $fanart) => $this->notifier->fanartRemovedWithContent(
                $fanart,
                $event->story->title,
                $event->story->slug,
            ));
    }
}
