<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Comment\Public\Api\Contracts\DefaultCommentPolicy;
use App\Domains\Comment\Public\Events\CommentEdited;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);
describe('Editing comments', function () {
    describe('Access', function () {
        it('should return 401 if user is not Logged', function () {
            $alice = alice($this);
            $this->actingAs($alice);
            $commentId = createComment('default', 1, 'Hello', null);

            $this->actingAsGuest();

            expect(function () use ($commentId) {
                editComment($commentId, 'Hello');
            })->toThrow(UnauthorizedException::class);
        });

        it('should not allow to edit someone else\'s comment', function () {
            $user = alice($this, roles: [Roles::USER]);
            $this->actingAs($user);
            $commentId = createComment('default', 1, 'Hello', null);

            $otherUser = bob($this, roles: [Roles::USER]);
            $this->actingAs($otherUser);

            expect(function () use ($commentId) {
                editComment($commentId, 'Hello');
            })->toThrow(UnauthorizedException::class);
        });

        it('should work for users on probation (simple user role)', function () {
            $user = alice($this, roles: [Roles::USER]);
            $this->actingAs($user);
            $commentId = createComment('default', 1, 'Hello', null);

            editComment($commentId, 'New content');
            $comment = getComment($commentId);
            expect($comment->body)->toContain('New content');
        });

        it('should work for confirmed users (user_confirmed role)', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);
            $commentId = createComment('default', 1, 'Hello', null);

            editComment($commentId, 'New content');
            $comment = getComment($commentId);
            expect($comment->body)->toContain('New content');
        });
    });

    describe('Policies', function () {
        it('throws an error if min body length (once HTML stripped) is not matching the policy minimum for root comments', function () {
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register('default', new class extends DefaultCommentPolicy {
                public function getRootCommentMinLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);
            $commentId = createComment('default', 1, generateDummyText(15), null);


            expect(function () use ($commentId) {
                editComment($commentId, generateDummyText(5));
            })->toThrow(ValidationException::withMessages(['body' => ['Comment too short']]));
        });

        it('throws an error if max body length (once HTML stripped) is not matching the policy maximum for root comments', function () {
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register('default', new class extends DefaultCommentPolicy {
                public function getRootCommentMaxLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);
            $commentId = createComment('default', 1, generateDummyText(8), null);

            expect(function () use ($commentId) {
                editComment($commentId, generateDummyText(20));
            })->toThrow(ValidationException::withMessages(['body' => ['Comment too long']]));
        });

        it('throws an error if min body length (once HTML stripped) is not matching the policy minimum for replies', function () {
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register('default', new class extends DefaultCommentPolicy {
                public function getReplyCommentMinLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);
            $commentId = createComment('default', 1, generateDummyText(15), null);
            $childCommentId = createComment('default', 1, generateDummyText(15), $commentId);

            expect(function () use ($childCommentId) {
                editComment($childCommentId, generateDummyText(5));
            })->toThrow(ValidationException::withMessages(['body' => ['Comment too short']]));
        });

        it('throws an error if max body length (once HTML stripped) is not matching the policy maximum for replies', function () {
            /** @var CommentPolicyRegistry $registry */
            $registry = app(CommentPolicyRegistry::class);
            $registry->register('default', new class extends DefaultCommentPolicy {
                public function getReplyCommentMaxLength(): ?int
                {
                    return 10;
                }
            });

            $user = alice($this);
            $this->actingAs($user);
            $commentId = createComment('default', 1, generateDummyText(10), null);
            $childCommentId = createComment('default', 1, generateDummyText(10), $commentId);

            expect(function () use ($childCommentId) {
                editComment($childCommentId, generateDummyText(20));
            })->toThrow(ValidationException::withMessages(['body' => ['Comment too long']]));
        });
    });

    describe('Root content', function () {
        it('should allow to create a edit a root comment', function () {
            $user = alice($this);
            $this->actingAs($user);
            $commentId = createComment('default', 1, 'Hello', null);

            editComment($commentId, 'Hello world');

            $comment = getComment($commentId);
            expect($comment->body)->toContain('Hello world');
        });

        it('should sanitize the content of the comment', function () {
            $user = alice($this);
            $this->actingAs($user);
            $commentId = createComment('default', 1, 'Hello', null);
            editComment($commentId, '<script>alert("xss");</script>World');
            $comment = getComment($commentId);
            expect($comment->body)->toContain('World');
        });
    });

    describe('Events', function () {
        it('emits Comment.Edited with before/after snapshots for a root comment edit', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $entityType = 'default';
            $entityId = 77;

            $body = '<p>' . str_repeat('hello ', 20) . '</p>';
            $commentId = $api->create(new CommentToCreateDto($entityType, $entityId, $body, null));

            $newBody = '<p>' . str_repeat('world ', 10) . '</p>';
            $api->edit($commentId, $newBody);

            /** @var CommentEdited|null $event */
            $event = latestEventOf(CommentEdited::name(), CommentEdited::class);
            expect($event)->not->toBeNull();

            expect($event->before->commentId)->toBe($commentId);
            expect($event->after->commentId)->toBe($commentId);
            expect($event->before->entityType)->toBe($entityType);
            expect($event->before->entityId)->toBe($entityId);
            expect($event->after->entityType)->toBe($entityType);
            expect($event->after->entityId)->toBe($entityId);

            // Counts should reflect change
            expect($event->before->charCount)->not->toBe($event->after->charCount);
        });

        it('emits Comment.Edited with before/after snapshots for a reply edit', function () {
            $user = alice($this);
            $this->actingAs($user);

            $entityType = 'default';
            $entityId = 78;

            $rootBody = '<p>' . str_repeat('root ', 30) . '</p>';
            $rootId = createComment($entityType, $entityId, $rootBody, null);

            $replyBody = '<p>' . str_repeat('reply ', 15) . '</p>';
            $replyId = createComment($entityType, $entityId, $replyBody, $rootId);

            $newReplyBody = '<p>' . str_repeat('edited ', 8) . '</p>';
            editComment($replyId, $newReplyBody);

            /** @var CommentEdited|null $event */
            $event = latestEventOf(CommentEdited::name(), CommentEdited::class);
            expect($event)->not->toBeNull();

            expect($event->before->commentId)->toBe($replyId);
            expect($event->after->commentId)->toBe($replyId);
            expect($event->before->isReply)->toBeTrue();
            expect($event->before->parentCommentId)->toBe($rootId);
            expect($event->after->isReply)->toBeTrue();
            expect($event->after->parentCommentId)->toBe($rootId);
        });
    });
});
