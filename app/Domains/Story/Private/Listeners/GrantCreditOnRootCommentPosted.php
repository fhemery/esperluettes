<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Services\ChapterCreditService;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class GrantCreditOnRootCommentPosted implements ShouldDispatchAfterCommit
{
    public function __construct(
        private readonly ChapterCreditService $credits,
        private readonly CommentPublicApi $comments,
        private readonly ChapterService $chapters,
    ) {}

    public function handle(CommentPosted $event): void
    {
        $snap = $event->comment;
        // Only consider comments on chapters and root comments
        if ($snap->isReply || (string)$snap->entityType !== 'chapter') {
            return;
        }

        // Chapter must be published
        $chapter = Chapter::query()->find((int)$snap->entityId);
        if (!$chapter) return;
        if ((string)$chapter->status !== Chapter::STATUS_PUBLISHED) return;

        // Exclude self-comments (authors/co-authors of the chapter's story)
        $isAuthor = $this->chapters->isUserAuthorOfChapter((int)$snap->entityId, (int)$snap->authorId);
        if ($isAuthor) return;

        // Ensure user hasn't already posted a root comment on this chapter BEFORE this comment
        // The transaction ended before we make the check, so if user already participated, there are 2 comments.
        $nbComments = $this->comments->getNbRootComments('chapter', (int)$snap->entityId, (int)$snap->authorId);
        if ($nbComments > 1) return;

        // Grant credit (service enforces once per (user, chapter) via unique constraint and ignores duplicates)
        $this->credits->grantOne((int)$snap->authorId);
    }
}
