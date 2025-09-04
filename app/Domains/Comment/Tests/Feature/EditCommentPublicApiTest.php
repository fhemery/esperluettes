<?php

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Comment\PublicApi\CommentPolicyRegistry;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Comment\Contracts\DefaultCommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

describe('Access', function() {
    it('should return 401 if user is not Logged', function() {
        $alice = alice($this);
        $this->actingAs($alice);
        $commentId = createComment('default', 1, 'Hello', null);
        
        $this->actingAsGuest();

        expect(function() use ($commentId) {
            editComment($commentId, 'Hello');
        })->toThrow(UnauthorizedException::class);
    });

    it('should not allow to edit someone else\'s comment', function() {
        $user = alice($this, roles:[Roles::USER]);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);

        $otherUser = bob($this, roles:[Roles::USER]);
        $this->actingAs($otherUser);

        expect(function() use ($commentId) {
            editComment($commentId, 'Hello');
        })->toThrow(UnauthorizedException::class);
    });

    it('should work for users on probation (simple user role)', function() {
        $user = alice($this, roles:[Roles::USER]);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);

        editComment($commentId, 'New content');
        $comment = getComment($commentId);
        expect($comment->body)->toContain('New content');
    });

    it('should work for confirmed users (user_confirmed role)', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);

        editComment($commentId, 'New content');
        $comment = getComment($commentId);
        expect($comment->body)->toContain('New content');
    });
});

describe('Policies', function() {
    it('throws an error if min body length (once HTML stripped) is not matching the policy minimum for root comments', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('default', new class extends DefaultCommentPolicy {
            public function getRootCommentMinLength(): ?int { return 10; }
        });

        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, generateCommentText(15), null);


        expect(function() use ($commentId) {
            editComment($commentId, generateCommentText(5));
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too short']]));
    });

    it('throws an error if max body length (once HTML stripped) is not matching the policy maximum for root comments', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('default', new class extends DefaultCommentPolicy {
            public function getRootCommentMaxLength(): ?int { return 10; }
        });

        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, generateCommentText(8), null);

        expect(function() use ($commentId) {
            editComment($commentId, generateCommentText(20));
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too long']]));
    });

    it('throws an error if min body length (once HTML stripped) is not matching the policy minimum for replies', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('default', new class extends DefaultCommentPolicy {
            public function getReplyCommentMinLength(): ?int { return 10; }
        });

        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, generateCommentText(15), null);
        $childCommentId = createComment('default', 1, generateCommentText(15), $commentId);

        expect(function() use ($childCommentId) {
            editComment($childCommentId, generateCommentText(5));
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too short']]));
    });

    it('throws an error if max body length (once HTML stripped) is not matching the policy maximum for replies', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('default', new class extends DefaultCommentPolicy {
            public function getReplyCommentMaxLength(): ?int { return 10; }
        });

        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, generateCommentText(10), null);
        $childCommentId = createComment('default', 1, generateCommentText(10), $commentId);

        expect(function() use ($childCommentId) {
            editComment($childCommentId, generateCommentText(20));
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too long']]));
    });
});

describe('Root content', function() {
    it('should allow to create a edit a root comment', function() {
        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);
        
        editComment($commentId, 'Hello world');

        $comment = getComment($commentId);
        expect($comment->body)->toContain('Hello world');
    });

    it('should sanitize the content of the comment', function() {
        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);
        editComment($commentId, '<script>alert("xss");</script>World');
        $comment = getComment($commentId);
        expect($comment->body)->toContain('World');
    });
});