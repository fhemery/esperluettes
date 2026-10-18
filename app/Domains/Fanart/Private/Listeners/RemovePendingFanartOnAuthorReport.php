<?php

namespace App\Domains\Fanart\Private\Listeners;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Moderation\Public\Events\ReportSubmitted;
use App\Domains\Story\Public\Api\StoryPublicApi;

/**
 * An author reporting a pending fanart takes it off their review queue: the
 * report keeps its own copy of the file, the fanartist gets a plain decline.
 */
class RemovePendingFanartOnAuthorReport
{
    public function __construct(
        private readonly FanartService $fanarts,
        private readonly StoryPublicApi $storyApi,
    ) {
    }

    public function handle(ReportSubmitted $event): void
    {
        if ($event->topicKey !== 'fanart') {
            return;
        }

        $fanart = Fanart::find($event->entityId);
        if (! $fanart
            || $fanart->status !== FanartStatus::Pending
            || ! $this->storyApi->isAuthor($event->reportedByUserId, $fanart->story_id)
        ) {
            return;
        }

        $this->fanarts->decline($fanart, $event->reportedByUserId, null);
    }
}
