<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Private\Services\ChapterService;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Shared\Contracts\ProfilePublicApi;

class NotifyAuthorsOnRootChapterComment
{
    public function __construct(
        private NotificationPublicApi $notifications,
        private ChapterService $chapters,
        private StoryService $stories,
        private ProfilePublicApi $profiles,
    ) {}

    public function handle(CommentPosted $event): void
    {
        $c = $event->comment;

        // Only for chapter root comments in this slice
        if ($c->entityType !== 'chapter' || $c->isReply) {
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

        // Authors of the story
        $authorIds = $this->stories->getAuthorIds((int) $story->id);

        // Exclude commenter from recipients
        $recipients = array_values(array_diff($authorIds, [(int) $c->authorId]));
        if (empty($recipients)) {
            return;
        }

        // Build payload per spec; enrich author fields from Profile domain
        $contentKey = 'story::notification.root_comment.posted';
        $authorProfile = $this->profiles->getPublicProfile((int) $c->authorId);
        $authorName = $authorProfile?->display_name ?? '';
        $authorSlug = $authorProfile?->slug ?? '';
        $contentData = [
            'author_name' => $authorName,
            'author_url' => $authorSlug !== '' ? route('profile.show', ['profile' => $authorSlug]) : '',
            'chapter_name' => (string) ($chapter->title ?? ''),
            'chapter_url_with_comment' => route('chapters.show', [
                'storySlug' => (string) $story->slug,
                'chapterSlug' => (string) $chapter->slug,
            ]) . '#comments',
        ];

        $this->notifications->createNotification($recipients, $contentKey, $contentData, (int) $c->authorId);
    }
}
