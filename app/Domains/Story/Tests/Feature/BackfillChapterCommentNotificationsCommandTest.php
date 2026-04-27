<?php

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\DTO\CommentSnapshot;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Public\Notifications\ChapterCommentNotification;
use App\Domains\Story\Public\Notifications\ChapterRootCommentNotification;
use App\Domains\Story\Public\Notifications\ChapterReplyCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('BackfillChapterCommentNotificationsCommand', function () {
    beforeEach(function () {
        $bus = app(EventBus::class);
        $bus->registerEvent('Comment.Posted', CommentPosted::class);
    });

    it('deletes existing legacy notifications (story.chapter.comment) before processing', function () {
        $alice = alice($this);
        $bob = bob($this);

        // Register the legacy type so makeNotification can create it
        $factory = app(\App\Domains\Notification\Public\Services\NotificationFactory::class);
        $factory->register(
            type: ChapterCommentNotification::type(),
            class: ChapterCommentNotification::class,
            groupId: 'comments',
            nameKey: 'story::notification.settings.type_chapter_comment',
        );

        $content = new ChapterCommentNotification(
            commentId: 1,
            authorName: 'Test',
            authorSlug: 'test',
            chapterTitle: 'Chapter',
            storySlug: 'story-1',
            chapterSlug: 'chapter-1',
            isReply: false
        );
        makeNotification([$alice->id], $content, $bob->id);
        makeNotification([$bob->id], $content, $alice->id);

        expect(countNotificationsByKey('story.chapter.comment'))->toBe(2);

        Artisan::call('story:backfill-chapter-comment-notifications');

        expect(countNotificationsByKey('story.chapter.comment'))->toBe(0);
    });

    it('processes Comment.Posted events and creates root comment notifications', function () {
        $author = alice($this);
        $commenter = bob($this);

        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);

        $bus = app(EventBus::class);
        $snapshot = new CommentSnapshot(
            commentId: 100,
            entityType: 'chapter',
            entityId: $chapter->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot));

        Artisan::call('story:backfill-chapter-comment-notifications');

        $notification = getLatestNotificationByKey('story.chapter.root_comment');
        expect($notification)->not->toBeNull();
        expect($notification->source_user_id)->toBe($commenter->id);

        $reads = getNotificationTargetUserIds($notification->id);
        expect($reads)->toContain($author->id);
    });

    it('skips events for non-chapter entities silently', function () {
        $bob = bob($this);

        $bus = app(EventBus::class);
        $snapshot = new CommentSnapshot(
            commentId: 200,
            entityType: 'story',
            entityId: 999,
            authorId: $bob->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot));

        Artisan::call('story:backfill-chapter-comment-notifications');

        expect(countNotificationsByKey('story.chapter.root_comment'))->toBe(0);
        expect(countNotificationsByKey('story.chapter.reply_comment'))->toBe(0);
    });

    it('skips events for missing chapters silently and reports in output', function () {
        $bob = bob($this);

        $bus = app(EventBus::class);
        $snapshot = new CommentSnapshot(
            commentId: 300,
            entityType: 'chapter',
            entityId: 999999,
            authorId: $bob->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot));

        $exitCode = Artisan::call('story:backfill-chapter-comment-notifications');
        $output = Artisan::output();

        expect($exitCode)->toBe(0);
        expect($output)->toContain('Processed: 1');
        expect($output)->toContain('Skipped: 1');
        expect($output)->toContain('Created: 0');
    });

    it('processes multiple events and reports statistics', function () {
        $author1 = alice($this);
        $author2 = bob($this);
        $commenter = carol($this);

        $this->actingAs($author1);
        $story1 = publicStory('Story 1', $author1->id);
        $chapter1 = createPublishedChapter($this, $story1, $author1, ['title' => 'Ch 1']);

        $this->actingAs($author2);
        $story2 = publicStory('Story 2', $author2->id);
        $chapter2 = createPublishedChapter($this, $story2, $author2, ['title' => 'Ch 2']);

        $bus = app(EventBus::class);

        $bus->emit(new CommentPosted(new CommentSnapshot(
            commentId: 400,
            entityType: 'chapter',
            entityId: $chapter1->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        )));

        $bus->emit(new CommentPosted(new CommentSnapshot(
            commentId: 401,
            entityType: 'chapter',
            entityId: $chapter2->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        )));

        $bus->emit(new CommentPosted(new CommentSnapshot(
            commentId: 402,
            entityType: 'chapter',
            entityId: 999999, // invalid
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        )));

        $exitCode = Artisan::call('story:backfill-chapter-comment-notifications');
        $output = Artisan::output();

        expect($exitCode)->toBe(0);
        expect($output)->toContain('Processed: 3');
        expect($output)->toContain('Created: 2');
        expect($output)->toContain('Skipped: 1');

        expect(countNotificationsByKey('story.chapter.root_comment'))->toBe(2);
    });

    it('is idempotent and can be run multiple times', function () {
        $author = alice($this);
        $commenter = bob($this);

        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);

        $bus = app(EventBus::class);
        $bus->emit(new CommentPosted(new CommentSnapshot(
            commentId: 500,
            entityType: 'chapter',
            entityId: $chapter->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        )));

        Artisan::call('story:backfill-chapter-comment-notifications');
        expect(countNotificationsByKey('story.chapter.root_comment'))->toBe(1);

        Artisan::call('story:backfill-chapter-comment-notifications');
        expect(countNotificationsByKey('story.chapter.root_comment'))->toBe(1);
    });

    it('handles reply comments correctly', function () {
        $author = alice($this);
        $rootCommenter = bob($this);
        $replyCommenter = carol($this);

        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);

        $this->actingAs($rootCommenter);
        $rootCommentId = createComment('chapter', $chapter->id, generateDummyText(150));

        $bus = app(EventBus::class);
        $bus->emit(new CommentPosted(new CommentSnapshot(
            commentId: 601,
            entityType: 'chapter',
            entityId: $chapter->id,
            authorId: $replyCommenter->id,
            isReply: true,
            parentCommentId: $rootCommentId,
            wordCount: 10,
            charCount: 50
        )));

        Artisan::call('story:backfill-chapter-comment-notifications');

        $rootNotifications  = getAllNotificationsByKey('story.chapter.root_comment');
        $replyNotifications = getAllNotificationsByKey('story.chapter.reply_comment');

        // 1 root (from createComment above) + 1 reply (from manual event)
        expect($rootNotifications)->toHaveCount(1);
        expect($replyNotifications)->toHaveCount(1);

        $rootReads = getNotificationTargetUserIds($rootNotifications->first()->id);
        expect($rootReads)->toContain($author->id);
        expect($rootReads)->not->toContain($rootCommenter->id);

        $replyReads = getNotificationTargetUserIds($replyNotifications->first()->id);
        expect($replyReads)->toContain($rootCommenter->id);
        expect($replyReads)->not->toContain($replyCommenter->id);
    });

    it('emits an audit event after backfill completes', function () {
        $author = alice($this);
        $commenter = bob($this);

        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);

        $bus = app(EventBus::class);
        $bus->emit(new CommentPosted(new CommentSnapshot(
            commentId: 700,
            entityType: 'chapter',
            entityId: $chapter->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        )));

        Artisan::call('story:backfill-chapter-comment-notifications');

        $eventApi = app(\App\Domains\Events\Public\Api\EventPublicApi::class);
        $auditEvent = $eventApi->latest('Story.ChapterCommentNotificationsBackfilled');

        expect($auditEvent)->not->toBeNull();
        expect($auditEvent->toPayload()['events_processed'])->toBe(1);
        expect($auditEvent->toPayload()['notifications_created'])->toBe(1);
        expect($auditEvent->toPayload()['notifications_deleted'])->toBe(1);
        expect($auditEvent->toPayload()['skipped'])->toBe(0);
    });
});
