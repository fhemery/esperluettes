<?php

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\DTO\CommentSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Notify authors on root chapter comment', function () {
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
