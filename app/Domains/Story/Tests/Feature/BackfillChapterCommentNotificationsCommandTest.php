<?php

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\DTO\CommentSnapshot;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Story\Public\Notifications\ChapterCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('BackfillChapterCommentNotificationsCommand', function () {
    beforeEach(function () {
        // Register event and notification type for tests
        $bus = app(EventBus::class);
        $bus->registerEvent('Comment.Posted', CommentPosted::class);
        
        $factory = app(\App\Domains\Notification\Public\Services\NotificationFactory::class);
        $factory->register(ChapterCommentNotification::type(), ChapterCommentNotification::class);
    });
    
    it('deletes existing notifications of type story.chapter.comment before processing', function () {
        $alice = alice($this);
        $bob = bob($this);
        
        // Create existing notifications
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
        
        // Verify they exist
        expect(countNotificationsByKey('story.chapter.comment'))->toBe(2);
        
        // Run command
        Artisan::call('story:backfill-chapter-comment-notifications');
        
        // Should have deleted old ones (no events exist, so no new ones created)
        expect(countNotificationsByKey('story.chapter.comment'))->toBe(0);
    });

    it('processes Comment.Posted events and creates notifications', function () {
        $author = alice($this);
        $commenter = bob($this);
        
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter One']);
        
        // Create a Comment.Posted event
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
        
        // Run command
        Artisan::call('story:backfill-chapter-comment-notifications');
        
        // Should have created notification
        $notification = getLatestNotificationByKey('story.chapter.comment');
            
        expect($notification)->not->toBeNull();
        expect($notification->source_user_id)->toBe($commenter->id);
        
        // Should have notified the author
        $reads = getNotificationTargetUserIds($notification->id);
        expect($reads)->toContain($author->id);
    });

    it('skips events for non-chapter entities silently', function () {
        $bob = bob($this);
        
        // Create a Comment.Posted event for a story (not chapter)
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
        
        // Run command
        Artisan::call('story:backfill-chapter-comment-notifications');
        
        // Should not have created any notification
        expect(countNotificationsByKey('story.chapter.comment'))->toBe(0);
    });

    it('skips events for missing chapters silently and reports in output', function () {
        $bob = bob($this);
        
        // Create event for non-existent chapter
        $bus = app(EventBus::class);
        $snapshot = new CommentSnapshot(
            commentId: 300,
            entityType: 'chapter',
            entityId: 999999, // doesn't exist
            authorId: $bob->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot));
        
        // Run command
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
        
        // Create two stories with chapters
        $this->actingAs($author1);
        $story1 = publicStory('Story 1', $author1->id);
        $chapter1 = createPublishedChapter($this, $story1, $author1, ['title' => 'Ch 1']);
        
        $this->actingAs($author2);
        $story2 = publicStory('Story 2', $author2->id);
        $chapter2 = createPublishedChapter($this, $story2, $author2, ['title' => 'Ch 2']);
        
        // Create events
        $bus = app(EventBus::class);
        
        $snapshot1 = new CommentSnapshot(
            commentId: 400,
            entityType: 'chapter',
            entityId: $chapter1->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot1));
        
        $snapshot2 = new CommentSnapshot(
            commentId: 401,
            entityType: 'chapter',
            entityId: $chapter2->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot2));
        
        // One invalid event
        $snapshot3 = new CommentSnapshot(
            commentId: 402,
            entityType: 'chapter',
            entityId: 999999,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot3));
        
        // Run command
        $exitCode = Artisan::call('story:backfill-chapter-comment-notifications');
        $output = Artisan::output();
        
        expect($exitCode)->toBe(0);
        expect($output)->toContain('Processed: 3');
        expect($output)->toContain('Created: 2');
        expect($output)->toContain('Skipped: 1');
        
        // Verify notifications were created
        expect(countNotificationsByKey('story.chapter.comment'))->toBe(2);
    });

    it('is idempotent and can be run multiple times', function () {
        $author = alice($this);
        $commenter = bob($this);
        
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);
        
        // Create event
        $bus = app(EventBus::class);
        $snapshot = new CommentSnapshot(
            commentId: 500,
            entityType: 'chapter',
            entityId: $chapter->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot));
        
        // Run command first time
        Artisan::call('story:backfill-chapter-comment-notifications');
        $firstCount = countNotificationsByKey('story.chapter.comment');
        expect($firstCount)->toBe(1);
        
        // Run command second time
        Artisan::call('story:backfill-chapter-comment-notifications');
        $secondCount = countNotificationsByKey('story.chapter.comment');
        
        // Should have same result (deleted old one, recreated it)
        expect($secondCount)->toBe(1);
    });

    it('handles reply comments correctly', function () {
        $author = alice($this);
        $rootCommenter = bob($this);
        $replyCommenter = carol($this);
        
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);
        
        // Create root comment via actual API to get parent comment ID
        // Note: createComment() already emits CommentPosted event, so we don't need to emit it again
        $this->actingAs($rootCommenter);
        $rootCommentId = createComment('chapter', $chapter->id, generateDummyText(150));
        
        // Emit reply event (manual emission for backfill testing)
        $bus = app(EventBus::class);
        $replySnapshot = new CommentSnapshot(
            commentId: 601,
            entityType: 'chapter',
            entityId: $chapter->id,
            authorId: $replyCommenter->id,
            isReply: true,
            parentCommentId: $rootCommentId,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($replySnapshot));
        
        // Run command
        Artisan::call('story:backfill-chapter-comment-notifications');
        
        // Should have created 2 notifications (one for root, one for reply)
        $notifications = getAllNotificationsByKey('story.chapter.comment');
            
        expect($notifications)->toHaveCount(2);
        
        // Root comment should notify author only
        $rootNotif = $notifications->firstWhere('source_user_id', $rootCommenter->id);
        $rootReads = getNotificationTargetUserIds($rootNotif->id);
        expect($rootReads)->toContain($author->id);
        expect($rootReads)->not->toContain($rootCommenter->id);
        
        // Reply should notify root commenter
        $replyNotif = $notifications->firstWhere('source_user_id', $replyCommenter->id);
        $replyReads = getNotificationTargetUserIds($replyNotif->id);
        expect($replyReads)->toContain($rootCommenter->id);
        expect($replyReads)->not->toContain($replyCommenter->id);
    });

    it('emits an audit event after backfill completes', function () {
        $author = alice($this);
        $commenter = bob($this);
        
        $this->actingAs($author);
        $story = publicStory('Test Story', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapter']);
        
        // Create event
        $bus = app(EventBus::class);
        $snapshot = new CommentSnapshot(
            commentId: 700,
            entityType: 'chapter',
            entityId: $chapter->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 10,
            charCount: 50
        );
        $bus->emit(new CommentPosted($snapshot));
        
        // Run command
        Artisan::call('story:backfill-chapter-comment-notifications');
        
        // Verify audit event was emitted
        $eventApi = app(\App\Domains\Events\Public\Api\EventPublicApi::class);
        $auditEvent = $eventApi->latest('Story.ChapterCommentNotificationsBackfilled');
        
        expect($auditEvent)->not->toBeNull();
        expect($auditEvent->toPayload()['events_processed'])->toBe(1);
        expect($auditEvent->toPayload()['notifications_created'])->toBe(1);
        // Note: deleted count is 1 because the notification was already created by the normal event flow
        expect($auditEvent->toPayload()['notifications_deleted'])->toBe(1);
        expect($auditEvent->toPayload()['skipped'])->toBe(0);
    });
});
