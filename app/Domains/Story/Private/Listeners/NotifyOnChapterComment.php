<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Public\Notifications\ChapterCommentNotification;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Comment\Public\Api\CommentPublicApi;

class NotifyOnChapterComment
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private ChapterService $chapters,
        private StoryService $stories,
        private ProfilePublicApi $profiles,
        private CommentPublicApi $comments,
    ) {}

    public function handle(CommentPosted $event): void
    {
        $c = $event->comment;

        // We only handle chapter comments in this listener
        if ($c->entityType !== 'chapter') {
            return;
        }

        // Resolve chapter and story within Story domain
        $chapter = $this->chapters->getChapterById((int) $c->entityId);
        if (!$chapter) {
            return;
        }

        $story = $this->stories->getStoryById((int) $chapter->story_id);
        if (!$story) {
            return;
        }

        // Enrich author fields (the user who posted this comment or reply)
        $authorProfile = $this->profiles->getPublicProfile((int) $c->authorId);
        $authorName = $authorProfile?->display_name ?? '';
        $authorSlug = $authorProfile?->slug ?? '';

        if ($c->isReply) {
            // Notify all participants in the thread (root author + all direct repliers), excluding current user
            if (!$c->parentCommentId) {
                return; // safety
            }
            $rootWithChildren = $this->comments->getComment((int)$c->parentCommentId, true);
            $rootAuthorId = (int) ($rootWithChildren->authorId ?? 0);
            $childAuthorIds = array_map(
                fn($child) => (int) ($child->authorId ?? 0),
                $rootWithChildren->children
            );
            $candidateRecipients = array_unique(array_merge([$rootAuthorId], $childAuthorIds));
            $recipients = array_values(array_filter($candidateRecipients, fn($id) => (int)$id > 0 && (int)$id !== (int)$c->authorId));
            if (empty($recipients)) {
                return;
            }

            $content = new ChapterCommentNotification(
                commentId: (int) $c->commentId,
                authorName: $authorName,
                authorSlug: $authorSlug,
                chapterTitle: (string) ($chapter->title ?? ''),
                storySlug: (string) $story->slug,
                chapterSlug: (string) $chapter->slug,
                isReply: true,
            );

            $this->notifications->createNotification($recipients, $content, (int) $c->authorId);
            return;
        }

        // Root comment: notify all story authors except the commenter
        $authorIds = $this->stories->getAuthorIds((int) $story->id);
        $recipients = array_values(array_diff($authorIds, [(int) $c->authorId]));
        if (empty($recipients)) {
            return;
        }

        $content = new ChapterCommentNotification(
            commentId: (int) $c->commentId,
            authorName: $authorName,
            authorSlug: $authorSlug,
            chapterTitle: (string) ($chapter->title ?? ''),
            storySlug: (string) $story->slug,
            chapterSlug: (string) $chapter->slug,
            isReply: false,
        );

        $this->notifications->createNotification($recipients, $content, (int) $c->authorId);
    }
}
