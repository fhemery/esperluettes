<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Private\Models\Comment;
use App\Domains\Comment\Private\Services\CommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Restoring comments after user reactivation', function () {

    it('restores authored root comments and makes them visible again', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story + published chapter as a valid target
        $story = publicStory('Reactivation Comment Target', $user->id);
        $chapter = createPublishedChapter($this, $story, $user);

        /** @var CommentService $comments */
        $comments = app(CommentService::class);

        // Post a root comment by the user
        $comment = $comments->postComment('chapter', (int) $chapter->id, (int) $user->id, 'Root by user');

        // Deactivate -> soft-delete
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);
        $afterDeact = $comments->countForAuthor('chapter', (int) $user->id, true);
        expect($afterDeact)->toBe(0);
        $c = Comment::withTrashed()->find($comment->id);
        expect($c?->trashed())->toBeTrue();

        // Act: reactivate -> restore
        app(AuthPublicApi::class)->activateUserById($user->id);

        // Assert: count includes it again
        $afterReact = $comments->countForAuthor('chapter', (int) $user->id, true);
        expect($afterReact)->toBe(1);

        // Assert: model not trashed
        $c = Comment::withTrashed()->find($comment->id);
        expect($c?->trashed())->toBeFalse();

        // Assert: listing shows it again
        $paged = $comments->getFor('chapter', (int) $chapter->id, 1, 20, false);
        $ids = collect($paged->items())->map(fn($cm) => (int)$cm->id)->all();
        expect(in_array((int)$comment->id, $ids, true))->toBeTrue();
    });

    it('restores authored non-root (reply) comments and makes them visible again', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Create a story + published chapter as a valid target
        $story = publicStory('Reactivation Reply Target', $user->id);
        $chapter = createPublishedChapter($this, $story, $user);

        /** @var CommentService $comments */
        $comments = app(CommentService::class);

        // Root by someone else
        $bob = bob($this, roles: [Roles::USER_CONFIRMED]);
        $root = $comments->postComment('chapter', (int) $chapter->id, (int) $bob->id, 'Root');

        // Reply by target user
        $reply = $comments->postComment('chapter', (int) $chapter->id, (int) $user->id, 'Reply by user', (int) $root->id);

        // Deactivate -> soft-delete reply
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);
        $rc = Comment::withTrashed()->find($reply->id);
        expect($rc?->trashed())->toBeTrue();

        // Act: reactivate -> restore
        app(AuthPublicApi::class)->activateUserById($user->id);

        // Assert: reply is not trashed and shows up in listing with children
        $rc = Comment::withTrashed()->find($reply->id);
        expect($rc?->trashed())->toBeFalse();

        $paged = $comments->getFor('chapter', (int) $chapter->id, 1, 20, true);
        $childIds = collect($paged->items())
            ->flatMap(fn($cm) => $cm->children->pluck('id')->all())
            ->map(fn($id) => (int)$id)
            ->all();
        expect(in_array((int)$reply->id, $childIds, true))->toBeTrue();
    });
});
