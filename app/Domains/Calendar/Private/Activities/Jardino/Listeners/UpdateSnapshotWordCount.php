<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Listeners;

use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoProgressService;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\ChapterDeleted;

final class UpdateSnapshotWordCount
{
    public function __construct(
        private readonly JardinoProgressService $progressService,
    ) {}

    public function handleChapterCreated(ChapterCreated $event): void
    {
        $this->progressService->updateSnapshotWordCount($event->storyId, $event->chapter->wordCount);
    }

    public function handleChapterUpdated(ChapterUpdated $event): void
    {
        $wordDelta = $event->after->wordCount - $event->before->wordCount;
        $this->progressService->updateSnapshotWordCount($event->storyId, $wordDelta);
    }

    public function handleChapterDeleted(ChapterDeleted $event): void
    {
        $this->progressService->updateSnapshotWordCount($event->storyId, -$event->chapter->wordCount);
    }
}
