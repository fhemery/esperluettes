<?php

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\DTO\CommentSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Notify authors chapter comment', function () {
    it('does nothing if entity is not a chapter', function () {

        $snapshot = new CommentSnapshot(
            commentId: 10,
            entityType: 'story', // not chapter
            entityId: 55,
            authorId: 101, // commenter
            isReply: false,
            parentCommentId: null,
            wordCount: 3,
            charCount: 15,
        );

        dispatchEvent(new CommentPosted($snapshot));

        $notif = getLatestNotificationByKey('story::notification.root_comment.posted');
        expect($notif)->toBeNull();
    });

    it('does nothing if authors cannot be resolved (e.g., chapter not found)', function () {

        $snapshot = new CommentSnapshot(
            commentId: 11,
            entityType: 'chapter',
            entityId: 999999, // assume not resolvable
            authorId: 202,
            isReply: false,
            parentCommentId: null,
            wordCount: 3,
            charCount: 15,
        );

        dispatchEvent(new CommentPosted($snapshot));

        $notif = getLatestNotificationByKey('story::notification.root_comment.posted');
        expect($notif)->toBeNull();
    });

    describe('when comment is a root comment', function () {
        it('notifies all authors of the story for a root chapter comment, excluding the commenter', function () {
            $author1 = alice($this);
            $author2 = bob($this);
            $commenter = carol($this);

            $expectedRecipients = [$author1->id, $author2->id];

            $this->actingAs($author1);
            $story1 = publicStory('Story', $author1->id);
            addCollaborator($story1->id, $author2->id, 'author');
            $chapter = createPublishedChapter($this, $story1, $author1, ['title' => 'Chapter One']);

            $snapshot = new CommentSnapshot(
                commentId: 12,
                entityType: 'chapter',
                entityId: $chapter->id,
                authorId: $commenter->id,
                isReply: false,
                parentCommentId: null,
                wordCount: 5,
                charCount: 25,
            );

            dispatchEvent(new CommentPosted($snapshot));

            $notif = getLatestNotificationByKey('story::notification.root_comment.posted');
            expect($notif)->not->toBeNull();
            $targets = getNotificationTargetUserIds((int)$notif->id);
            sort($targets);
            sort($expectedRecipients);
            expect($targets)->toEqual($expectedRecipients);

            // Validate payload values and source user
            $payload = $notif->content_data;
            if (is_string($payload)) {
                $payload = json_decode($payload, true) ?: [];
            }
            expect($payload)->toHaveKeys(['author_name', 'author_url', 'chapter_name', 'chapter_url_with_comment']);
            expect($payload['author_name'])->toBe("Carol");
            expect($payload['author_url'])->toBe(route('profile.show', ['profile' => 'carol']));
            expect($payload['chapter_name'])->toBe('Chapter One');
            expect($payload['chapter_url_with_comment'])->toBe(route('chapters.show', [
                'storySlug' => $story1->slug,
                'chapterSlug' => $chapter->slug,
            ]) . '#comments');
            expect((int)($notif->source_user_id ?? 0))->toBe($commenter->id);
        });
    });

    describe('when comment is a reply', function () {
        beforeEach(function () {
            $this->author = alice($this);
            $this->rootCommenter = carol($this);

            $this->actingAs($this->author);

            // Author creates story and chapter
            $this->story = publicStory('Story', $this->author->id);
            $this->chapter = createPublishedChapter($this, $this->story, $this->author, ['title' => 'Chapter One']);

            // Commenter creates root comment
            $this->actingAs($this->rootCommenter);
            $this->rootCommentId = createComment('chapter', $this->chapter->id, generateDummyText(150));

        });

        it('does warn the comment root author', function () {
            $this->actingAs($this->author);
            $snapshot = new CommentSnapshot(
                commentId: 59,
                entityType: 'chapter',
                entityId: $this->chapter->id,
                authorId: $this->author->id,
                isReply: true,
                parentCommentId: $this->rootCommentId,
                wordCount: 3,
                charCount: 15,
            );

            dispatchEvent(new CommentPosted($snapshot));

            $expectedRecipients = [$this->rootCommenter->id];

            $notif = getLatestNotificationByKey('story::notification.reply_comment.posted');
            expect($notif)->not->toBeNull();
            $targets = getNotificationTargetUserIds((int)$notif->id);
            expect($targets)->toEqual($expectedRecipients);
        });

        it('does also notify everyone who replied to the root comment, excluding current user', function() {
            // Author replies
            $this->actingAs($this->author);
            createComment('chapter', $this->chapter->id, generateDummyText(150), $this->rootCommentId); 

            // Bob replies as well
            $otherCommenter = bob($this);
            $this->actingAs($otherCommenter);
            createComment('chapter', $this->chapter->id, generateDummyText(150), $this->rootCommentId); 

            // Bob then writes one more comment and event is sent
            $this->actingAs($otherCommenter);
            $lastCommentId = createComment('chapter', $this->chapter->id, generateDummyText(150), $this->rootCommentId); 
            
            $snapshot = new CommentSnapshot(
                commentId: $lastCommentId,
                entityType: 'chapter',
                entityId: $this->chapter->id,
                authorId: $otherCommenter->id,
                isReply: true,
                parentCommentId: $this->rootCommentId,
                wordCount: 3,
                charCount: 15,
            );

            dispatchEvent(new CommentPosted($snapshot));

            $notif = getLatestNotificationByKey('story::notification.reply_comment.posted');
            expect($notif)->not->toBeNull();
            $targets = getNotificationTargetUserIds((int)$notif->id);
            
            // Author should be notified, because she answered to the thread
            // Root commenter as well
            // But bob will not, because he is the author of the last comment
            expect($targets)->toContain($this->rootCommenter->id);
            expect($targets)->toContain($this->author->id);
            expect($targets)->not->toContain($otherCommenter->id);
        });
    });
});
