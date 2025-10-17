<?php

declare(strict_types=1);

use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Public\Events\CommentContentModerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Comment moderation - Empty Content', function () {
    beforeEach(function () {
        $this->entityType = 'chapter';
        $this->entityId = 9999;

        $this->author = alice($this);
        $this->moderator = moderator($this);
        $this->viewer = bob($this);

        $this->actingAs($this->author);
        $this->commentId = createComment($this->entityType, $this->entityId, generateDummyText(150));

        $this->targetUrl = route('comments.moderation.empty-content', ['commentId' => $this->commentId]);
        $this->referer = route('comments.fragments', [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ]);
    });

    describe('Access', function () {
        it('redirects guests to login when accessing moderation empty-content', function () {
            Auth::logout();
            $this->post($this->targetUrl)->assertRedirect('/login');
        });

        it('denies access to non-moderators (user, user-confirmed) by redirecting to dashboard', function () {
            $this->actingAs($this->viewer)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));
        });

        it('allows moderator to empty and redirects back with success message', function () {
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('comment::moderation.empty_content.success'));
        });
    });

    describe('Empty content', function () {
        it('replaces the comment body with the translated default text and updates UI', function () {
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            // Assert DB
            $comment = Comment::query()->findOrFail($this->commentId);
            expect($comment->body)->toBe(e(__('comment::moderation.default_text')));
        });

        it('emits CommentContentModerated event when moderator empties the content', function () {
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var CommentContentModerated $event */
            $event = latestEventOf(CommentContentModerated::name(), CommentContentModerated::class);
            expect($event)->not->toBeNull();
            expect($event->commentId)->toBe($this->commentId);
            expect($event->entityType)->toBe($this->entityType);
            expect($event->entityId)->toBe($this->entityId);
        });
    });
});
