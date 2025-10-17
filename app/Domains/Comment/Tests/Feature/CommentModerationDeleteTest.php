<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Public\Events\CommentDeletedByModeration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Comment moderation - Delete', function () {
    beforeEach(function () {
        $this->entityType = 'default';
        $this->entityId = 1001;

        $this->author = alice($this);
        $this->viewer = bob($this);
        $this->moderator = moderator($this);

        // Build a small thread: root -> child1, child2
        $this->actingAs($this->author);
        $this->rootId = createComment($this->entityType, $this->entityId, 'Root comment');
        $this->child1Id = createComment($this->entityType, $this->entityId, 'First child', $this->rootId);
        $this->child2Id = createComment($this->entityType, $this->entityId, 'Second child', $this->rootId);

        $this->targetUrl = route('comments.moderation.delete', ['commentId' => $this->rootId]);
        $this->referer = route('comments.fragments', [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ]);
    });

    describe('Access', function () {
        it('redirects guests to login when accessing moderation delete', function () {
            Auth::logout();
            $this->delete($this->targetUrl)->assertRedirect('/login');
        });

        it('denies access to non-moderators (user, user-confirmed) by redirecting to dashboard', function () {
            $this->actingAs($this->viewer)
                ->delete($this->targetUrl)
                ->assertRedirect(route('dashboard'));
        });

        it('allows moderator to delete and redirects back with success message', function () {
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->delete($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('comment::moderation.delete.success'));
        });
    });

    describe('Delete non-root comment', function () {
        it('deletes only the selected comment subtree (non-root) and preserves parent and siblings', function () {
            // Arrange: add a grandchild under child1 and a sibling under root
            $siblingId = createComment($this->entityType, $this->entityId, 'Third child', $this->rootId);

            // Sanity
            expect(Comment::query()->whereKey($this->rootId)->exists())->toBeTrue();
            expect(Comment::query()->whereKey($this->child1Id)->exists())->toBeTrue();
            expect(Comment::query()->whereKey($this->child2Id)->exists())->toBeTrue();
            expect(Comment::query()->whereKey($siblingId)->exists())->toBeTrue();

            // Act: delete non-root child1
            $target = route('comments.moderation.delete', ['commentId' => $this->child1Id]);
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->delete($target)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('comment::moderation.delete.success'));

            // Assert: child1 and its grandchild removed, others intact
            expect(Comment::query()->whereKey($this->child1Id)->exists())->toBeFalse();
            expect(Comment::query()->whereKey($this->rootId)->exists())->toBeTrue();
            expect(Comment::query()->whereKey($this->child2Id)->exists())->toBeTrue();
            expect(Comment::query()->whereKey($siblingId)->exists())->toBeTrue();
        });

        it('emits CommentDeletedByModeration for non-root comment deletion', function () {
            $target = route('comments.moderation.delete', ['commentId' => $this->child1Id]);
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->delete($target)
                ->assertRedirect($this->referer);

            /** @var CommentDeletedByModeration $event */
            $event = latestEventOf(CommentDeletedByModeration::name(), CommentDeletedByModeration::class);
            expect($event)->not->toBeNull();
            expect($event->commentId)->toBe($this->child1Id);
            expect($event->entityType)->toBe($this->entityType);
            expect($event->entityId)->toBe($this->entityId);
            expect($event->isRoot)->toBeFalse();
            expect($event->authorId)->toBe($this->author->id);
        });
    });

    describe('Delete cascade', function () {
        it('deletes the comment and its children', function () {
            // Sanity
            expect(Comment::query()->whereKey($this->rootId)->exists())->toBeTrue();
            expect(Comment::query()->whereKey($this->child1Id)->exists())->toBeTrue();
            expect(Comment::query()->whereKey($this->child2Id)->exists())->toBeTrue();

            // Act
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->delete($this->targetUrl)
                ->assertRedirect($this->referer);

            // Assert cascade: all gone
            expect(Comment::query()->whereKey($this->rootId)->exists())->toBeFalse();
            expect(Comment::query()->whereKey($this->child1Id)->exists())->toBeFalse();
            expect(Comment::query()->whereKey($this->child2Id)->exists())->toBeFalse();
        });
    });

    describe('Event', function () {
        it('emits CommentDeletedByModeration event when moderator deletes a comment', function () {
            $this->from($this->referer)
                ->actingAs($this->moderator)
                ->delete($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var CommentDeletedByModeration $event */
            $event = latestEventOf(CommentDeletedByModeration::name(), CommentDeletedByModeration::class);
            expect($event)->not->toBeNull();
            expect($event->commentId)->toBe($this->rootId);
            expect($event->entityType)->toBe($this->entityType);
            expect($event->entityId)->toBe($this->entityId);
            expect($event->isRoot)->toBeTrue();
            expect($event->authorId)->toBe($this->author->id);
        });
    });
});
