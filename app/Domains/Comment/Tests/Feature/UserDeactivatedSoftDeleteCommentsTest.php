<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Services\CommentService;
use App\Domains\Story\Private\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Soft deleting comments after user deactivation', function () {

    it('soft-deletes authored comments and hides them from counts/lists', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        $entityType='default';
       $entityId=42;

        /** @var CommentService $comments */
        $comments = app(CommentService::class);

        // Post a root comment by the user on the chapter
        $comment = $comments->postComment($entityType, $entityId, (int) $user->id, 'Hello world');

        // Sanity: the author has 1 root comment target counted
        $countBefore = $comments->countForAuthor($entityType, (int) $user->id, true);
        expect($countBefore)->toBe(1);

        // Act: deactivate via AuthPublicApi (emits UserDeactivated)
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);

        // Assert: counts no longer include the user's comments
        $countAfter = $comments->countForAuthor($entityType, (int) $user->id, true);
        expect($countAfter)->toBe(0);

        // Assert: comment row still exists but is soft-deleted
        $c = Comment::withTrashed()->find($comment->id);
        expect($c)->not->toBeNull();
        expect($c->trashed())->toBeTrue();

        // Assert: chapter-level listing does not include it
        $paged = $comments->getFor($entityType, $entityId, 1, 20, false);
        $ids = collect($paged->items())->map(fn($cm) => (int)$cm->id)->all();
        expect(in_array((int)$comment->id, $ids, true))->toBeFalse();
    });

    it('soft-deletes authored non-root (reply) comments and hides them from lists', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        $entityType='default';
        $entityId=42;

        /** @var CommentService $comments */
        $comments = app(CommentService::class);

        // Root by someone else
        $bob = bob($this, roles: [Roles::USER_CONFIRMED]);
        $root = $comments->postComment($entityType, $entityId, (int) $bob->id, 'Root');

        // Reply by the target user
        $reply = $comments->postComment($entityType, $entityId, (int) $user->id, 'Reply', (int) $root->id);

        // Sanity: list with children contains the reply id
        $pagedBefore = $comments->getFor($entityType, $entityId, 1, 20, true);
        $childIdsBefore = collect($pagedBefore->items())
            ->flatMap(fn($cm) => $cm->children->pluck('id')->all())
            ->map(fn($id) => (int)$id)
            ->all();
        expect(in_array((int)$reply->id, $childIdsBefore, true))->toBeTrue();

        // Act: deactivate
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);

        // Assert: reply is soft-deleted
        $c = Comment::withTrashed()->find($reply->id);
        expect($c)->not->toBeNull();
        expect($c->trashed())->toBeTrue();

        // Assert: it is removed from listing
        $pagedAfter = $comments->getFor($entityType, $entityId, 1, 20, true);
        $childIdsAfter = collect($pagedAfter->items())
            ->flatMap(fn($cm) => $cm->children->pluck('id')->all())
            ->map(fn($id) => (int)$id)
            ->all();
        expect(in_array((int)$reply->id, $childIdsAfter, true))->toBeFalse();
    });
});
