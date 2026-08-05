<?php

namespace App\Domains\News\Private\Listeners;

use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Public\Notifications\NewsReplyCommentNotification;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;

class NotifyOnNewsComment
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private CommentPublicApi $comments,
        private ProfilePublicApi $profiles,
    ) {}

    /**
     * Handle a CommentPosted event.
     *
     * @param CommentPosted $event
     * @param \DateTime|null $eventDate Used for backfilling, to override notification timestamp
     * @return void
     */
    public function handle(CommentPosted $event, ?\DateTime $eventDate = null): void
    {
        $c = $event->comment;

        // We only handle news comments in this listener
        if ($c->entityType !== 'news') {
            return;
        }

        // A root comment on a news article notifies nobody
        if (!$c->isReply) {
            return;
        }

        if (!$c->parentCommentId) {
            return; // safety
        }

        $news = News::query()->find((int) $c->entityId);
        if (!$news) {
            return;
        }

        // Notify all participants in the thread (root author + all direct repliers), excluding current user
        $rootWithChildren = $this->comments->getCommentInternal((int) $c->parentCommentId, true, 0);
        $rootAuthorId = (int) ($rootWithChildren->authorId ?? 0);
        $childAuthorIds = array_map(
            fn($child) => (int) ($child->authorId ?? 0),
            $rootWithChildren->children
        );
        $candidateRecipients = array_unique(array_merge([$rootAuthorId], $childAuthorIds));
        $recipients = array_values(array_filter(
            $candidateRecipients,
            fn($id) => (int) $id > 0 && (int) $id !== (int) $c->authorId
        ));
        if (empty($recipients)) {
            return;
        }

        $authorProfile = $this->profiles->getPublicProfile((int) $c->authorId);

        $content = new NewsReplyCommentNotification(
            commentId: (int) $c->commentId,
            authorName: $authorProfile?->display_name ?? '',
            authorSlug: $authorProfile?->slug ?? '',
            newsTitle: (string) $news->title,
            newsSlug: (string) $news->slug,
        );

        $this->notifications->createNotification($recipients, $content, (int) $c->authorId, $eventDate);
    }
}
