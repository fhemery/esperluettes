<?php

declare(strict_types=1);

use App\Domains\Comment\Public\Events\CommentDeletedByModeration;
use App\Domains\Story\Private\Services\ChapterCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('CommentDeletedListener', function () {
    beforeEach(function () {
        $this->author = alice($this);
        // initialize credits row for easier assertions
        app(ChapterCreditService::class)->grantInitialOnRegistration($this->author->id);
    });

    it('decrements one credit when a root chapter comment is deleted by moderation with known author', function () {
        $credits = app(ChapterCreditService::class);
        $before = $credits->availableForUser($this->author->id);

        // Simulate event
        $event = new CommentDeletedByModeration(
            commentId: 111,
            entityType: 'chapter',
            entityId: 222,
            isRoot: true,
            authorId: $this->author->id,
        );
        // Directly invoke listener via container
        app(\App\Domains\Story\Private\Listeners\CommentDeletedListener::class)->handle($event);

        $after = $credits->availableForUser($this->author->id);
        expect($after)->toBe($before - 1);
    });

    it('does nothing for non-root deleted comments', function () {
        $credits = app(ChapterCreditService::class);
        $before = $credits->availableForUser($this->author->id);

        $event = new CommentDeletedByModeration(
            commentId: 111,
            entityType: 'chapter',
            entityId: 222,
            isRoot: false,
            authorId: $this->author->id,
        );
        app(\App\Domains\Story\Private\Listeners\CommentDeletedListener::class)->handle($event);

        $after = $credits->availableForUser($this->author->id);
        expect($after)->toBe($before);
    });

    it('does nothing when authorId is null', function () {
        $credits = app(ChapterCreditService::class);
        $before = $credits->availableForUser($this->author->id);

        $event = new CommentDeletedByModeration(
            commentId: 111,
            entityType: 'chapter',
            entityId: 222,
            isRoot: true,
            authorId: null,
        );
        app(\App\Domains\Story\Private\Listeners\CommentDeletedListener::class)->handle($event);

        $after = $credits->availableForUser($this->author->id);
        expect($after)->toBe($before);
    });

    it('does nothing for non-chapter entityType', function () {
        $credits = app(ChapterCreditService::class);
        $before = $credits->availableForUser($this->author->id);

        $event = new CommentDeletedByModeration(
            commentId: 111,
            entityType: 'story',
            entityId: 222,
            isRoot: true,
            authorId: $this->author->id,
        );
        app(\App\Domains\Story\Private\Listeners\CommentDeletedListener::class)->handle($event);

        $after = $credits->availableForUser($this->author->id);
        expect($after)->toBe($before);
    });
});
