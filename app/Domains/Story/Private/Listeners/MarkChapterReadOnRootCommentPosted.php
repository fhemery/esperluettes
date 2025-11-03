<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Services\ReadingProgressService;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class MarkChapterReadOnRootCommentPosted implements ShouldDispatchAfterCommit
{
    public function __construct(
        private readonly ReadingProgressService $reading,
        private readonly ChapterService $chapters,
    ) {}

    public function handle(CommentPosted $event): void
    {
        $snap = $event->comment;
        // Only root comments on chapters
        if ($snap->isReply || (string) $snap->entityType !== 'chapter') {
            return;
        }

        /** @var Chapter|null $chapter */
        $chapter = Chapter::query()->find((int) $snap->entityId);
        if (!$chapter) return;
        if ((string) $chapter->status !== Chapter::STATUS_PUBLISHED) return;

        // Exclude authors/co-authors
        $isAuthor = $this->chapters->isUserAuthorOfChapter((int)$snap->entityId, (int)$snap->authorId);
        if ($isAuthor) return;

        /** @var Story|null $story */
        $story = Story::query()->find((int) $chapter->story_id);
        if (!$story) return;

        // Idempotent inside service
        $this->reading->markRead((int) $snap->authorId, $story, $chapter);
    }
}
