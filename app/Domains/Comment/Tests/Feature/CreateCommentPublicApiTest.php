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
        expect(function() {
            createComment('default', 1, 'Hello', null);
        })->toThrow(UnauthorizedException::class);
    });

    it('should return 401 is user is not verified', function() {
        expect(function() {
            $user = alice($this, roles: [], isVerified: false);
            $this->actingAs($user);
            createComment('default', 1, 'Hello', null);
        })->toThrow(UnauthorizedException::class);
    });

    it('should work for users on probation (simple user role)', function() {
        $user = alice($this, roles:[Roles::USER]);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('should work for confirmed users (user_confirmed role)', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });
});

describe('Policies', function() {
    it('throws an error if validateCreate fails', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('default', new class extends DefaultCommentPolicy {
            public function validateCreate(CommentToCreateDto $dto): void
            {
                $len = mb_strlen(trim(strip_tags($dto->body)));
                if ($len > $this->getRootCommentMaxLength()) {
                    throw ValidationException::withMessages(['body' => ['Comment too long']]);
                }
            }
            public function getRootCommentMaxLength(): ?int { return 140; }
        });

        $user = alice($this);
        $this->actingAs($user);

        $long = str_repeat('a', 141);
        expect(function() use ($long) {
            createComment('default', 1, $long, null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too long']]));

        $ok = str_repeat('b', 140);
        $id = createComment('default', 1, $ok, null);
        expect($id)->toBeGreaterThan(0);
    });

    it('throws an error if min body length (once HTML stripped) is not matching the policy minimum for root comments', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('default', new class extends DefaultCommentPolicy {
            public function getRootCommentMinLength(): ?int { return 10; }
        });

        $user = alice($this);
        $this->actingAs($user);

        expect(function() {
            createComment('default', 1, '<p>Hello</p><p></p>', null);
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

        expect(function() {
            createComment('default', 1, 'Hello world', null);
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
        createComment('default', 1, 'Hello', null);

        expect(function() {
            createComment('default', 1, '<p>Hello</p><p></p>', 1);
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
        createComment('default', 1, 'Hello', null);

        expect(function() {
            createComment('default', 1, '<p>' . generateCommentText(11) . '</p>', 1);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too long']]));
    });

    it('throws an error if creating root comment is not allowed', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('default', new class extends DefaultCommentPolicy {
            public function canCreateRoot(int $entityId, int $userId): bool { return false; }
        });

        $user = alice($this);
        $this->actingAs($user);

        expect(function() {
            createComment('default', 1, 'Hello world', null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment not allowed']]));
    });
});

describe('Root content', function() {
    it('should allow to create a root comment', function() {
        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('should sanitize the content of the comment', function() {
        $user = alice($this);
        $this->actingAs($user);
        $commentId = createComment('default', 1, '<script>alert("xss");</script>Hello', null);
        $comment = getComment($commentId);
        expect($comment->body)->toContain('Hello');
    });
});

describe('Child comments', function () {

    it('should not allow to create a comment with non matching entity type', function () {
        // Auth as confirmed user
        $user = alice($this);
        $this->actingAs($user);

        $commentId = createComment('default', 123, 'Hello', null);

        expect(function() use ($commentId) {
            createComment('other', 123, 'Hello', $commentId);
        })->toThrow(ValidationException::class);
    });

    it('should not allow to create a comment with non matching entity id', function () {
        // Auth as confirmed user
        $user = alice($this);
        $this->actingAs($user);

        $commentId = createComment('default', 123, 'Hello', null);

        expect(function() use ($commentId) {
            createComment('default', 456, 'Hello', $commentId);
        })->toThrow(ValidationException::class);
    });

    it('should allow to create a comment with a parent comment', function () {
        // Auth as confirmed user
        $user = alice($this);
        $this->actingAs($user);

        $commentId = createComment('default', 123, 'Hello', null);

        $childCommentId = createComment('default', 123, 'Hello', $commentId);
        expect($childCommentId)->toBeGreaterThan(0);

        $comment = listComments('default', 123);
        expect($comment->items)->toHaveCount(1);
        expect($comment->items[0]->children)->toHaveCount(1);
        expect($comment->items[0]->children[0]->body)->toContain('Hello');
    });

    it('should not count the comment replies in the pagination', function () {
        // Auth as confirmed user
        $user = alice($this);
        $this->actingAs($user);

        $commentIds = createSeveralComments(5, 'default', 123, 'Hello', null);

        createComment('default', 123, 'Hello', $commentIds[0]);

        $response = listComments('default', 123);
        // We only have 5 comments in the pagination, because the 6th one is in the replies, so will be loaded inside the first comment
        expect($response->total)->toBe(5);
        expect($response->items)->toHaveCount(5);
    });

    it('should not allow to create a comment with a parent comment that is not a root comment', function () {
        // Auth as confirmed user
        $user = alice($this);
        $this->actingAs($user);

        $commentId = createComment('default', 123, 'Hello', null);
        $childCommentId = createComment('default', 123, 'Hello from child', $commentId);

        expect(function() use ($childCommentId) {
            createComment('default', 123, 'Hello', $childCommentId);
        })->toThrow(ValidationException::class);
    });
});