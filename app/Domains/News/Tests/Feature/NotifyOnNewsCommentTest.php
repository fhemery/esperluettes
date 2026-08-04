<?php

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\DTO\CommentSnapshot;
use App\Domains\News\Private\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function newsForComments(int $createdBy, array $overrides = []): News
{
    return News::factory()->published()->create(array_merge([
        'created_by' => $createdBy,
    ], $overrides));
}

describe('Notify participants on news comment', function () {
    it('does nothing when the comment is not on a news article', function () {
        $snapshot = new CommentSnapshot(
            commentId: 10,
            entityType: 'chapter',
            entityId: 55,
            authorId: 101,
            isReply: true,
            parentCommentId: 9,
            wordCount: 3,
            charCount: 15,
        );

        dispatchEvent(new CommentPosted($snapshot));

        expect(getLatestNotificationByKey('news.reply_comment'))->toBeNull();
    });

    it('does nothing for a root comment on a news article', function () {
        $author = admin($this);
        $news = newsForComments($author->id);

        $commenter = alice($this);
        $this->actingAs($commenter);
        $rootId = createComment('news', $news->id, generateDummyText(30), null);

        $snapshot = new CommentSnapshot(
            commentId: $rootId,
            entityType: 'news',
            entityId: $news->id,
            authorId: $commenter->id,
            isReply: false,
            parentCommentId: null,
            wordCount: 5,
            charCount: 30,
        );

        dispatchEvent(new CommentPosted($snapshot));

        expect(getLatestNotificationByKey('news.reply_comment'))->toBeNull();
    });

    it('does nothing when the article cannot be resolved', function () {
        $snapshot = new CommentSnapshot(
            commentId: 11,
            entityType: 'news',
            entityId: 999999,
            authorId: 202,
            isReply: true,
            parentCommentId: 12,
            wordCount: 3,
            charCount: 15,
        );

        dispatchEvent(new CommentPosted($snapshot));

        expect(getLatestNotificationByKey('news.reply_comment'))->toBeNull();
    });

    it('notifies the root author and prior repliers, excluding the replier', function () {
        $author = admin($this);
        $news = newsForComments($author->id);

        $rootCommenter = alice($this);
        $firstReplier = bob($this);
        $secondReplier = carol($this);

        $this->actingAs($rootCommenter);
        $rootId = createComment('news', $news->id, generateDummyText(30), null);

        $this->actingAs($firstReplier);
        createComment('news', $news->id, generateDummyText(10), $rootId);

        $this->actingAs($secondReplier);
        $replyId = createComment('news', $news->id, generateDummyText(10), $rootId);

        $snapshot = new CommentSnapshot(
            commentId: $replyId,
            entityType: 'news',
            entityId: $news->id,
            authorId: $secondReplier->id,
            isReply: true,
            parentCommentId: $rootId,
            wordCount: 3,
            charCount: 10,
        );

        dispatchEvent(new CommentPosted($snapshot));

        $notif = getLatestNotificationByKey('news.reply_comment');
        expect($notif)->not->toBeNull();

        $targets = getNotificationTargetUserIds((int) $notif->id);
        sort($targets);
        $expected = [$rootCommenter->id, $firstReplier->id];
        sort($expected);

        expect($targets)->toEqual($expected);
        expect($notif->source_user_id)->toBe($secondReplier->id);
    });

    it('does not notify the replier when they reply to their own thread', function () {
        $author = admin($this);
        $news = newsForComments($author->id);

        $commenter = alice($this);
        $this->actingAs($commenter);
        $rootId = createComment('news', $news->id, generateDummyText(30), null);
        $replyId = createComment('news', $news->id, generateDummyText(10), $rootId);

        $snapshot = new CommentSnapshot(
            commentId: $replyId,
            entityType: 'news',
            entityId: $news->id,
            authorId: $commenter->id,
            isReply: true,
            parentCommentId: $rootId,
            wordCount: 3,
            charCount: 10,
        );

        dispatchEvent(new CommentPosted($snapshot));

        expect(getLatestNotificationByKey('news.reply_comment'))->toBeNull();
    });

    it('carries the article title, slug and comment id in the payload', function () {
        $author = admin($this);
        $news = newsForComments($author->id, ['title' => 'Payload News', 'slug' => 'payload-news']);

        $rootCommenter = alice($this);
        $replier = bob($this);

        $this->actingAs($rootCommenter);
        $rootId = createComment('news', $news->id, generateDummyText(30), null);

        $this->actingAs($replier);
        $replyId = createComment('news', $news->id, generateDummyText(10), $rootId);

        $snapshot = new CommentSnapshot(
            commentId: $replyId,
            entityType: 'news',
            entityId: $news->id,
            authorId: $replier->id,
            isReply: true,
            parentCommentId: $rootId,
            wordCount: 3,
            charCount: 10,
        );

        dispatchEvent(new CommentPosted($snapshot));

        $notif = getLatestNotificationByKey('news.reply_comment');
        expect($notif)->not->toBeNull();

        $payload = $notif->content_data;
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }

        expect($payload)->toHaveKeys([
            'comment_id', 'author_name', 'author_slug', 'news_title', 'news_slug',
        ]);
        expect($payload['comment_id'])->toBe($replyId);
        expect($payload['news_title'])->toBe('Payload News');
        expect($payload['news_slug'])->toBe($news->slug);
        expect($payload['author_name'])->toBe('Bob');
        expect($payload['author_slug'])->toBe('bob');
    });
});
