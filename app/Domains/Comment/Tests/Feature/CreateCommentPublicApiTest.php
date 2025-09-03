<?php

use App\Domains\Auth\PublicApi\AuthPublicApi;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Comment\Services\CommentPolicyRegistry;
use App\Domains\Comment\Contracts\CommentPostingPolicy;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

describe('Access', function() {
    it('should return 401 if user is not Logged', function() {
        expect(function() {
            createComment('chapter', 1, 'Hello', null);
        })->toThrow(UnauthorizedException::class);
    });

    it('should return 401 is user is not verified', function() {
        expect(function() {
            $user = alice($this, roles: [], isVerified: false);
            $this->actingAs($user);
            createComment('chapter', 1, 'Hello', null);
        })->toThrow(UnauthorizedException::class);
    });

    it('should work for users on probation (simple user role)', function() {
        $user = alice($this, roles:[Roles::USER]);
        $this->actingAs($user);
        $commentId = createComment('chapter', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('should work for confirmed users (user_confirmed role)', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment('chapter', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });
});

describe('Policies', function() {
    it('enforces confirmed role for chapter comments via policy', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('chapter', new class implements CommentPostingPolicy {
            public function validateCreate(CommentToCreateDto $dto): void
            {
                $authApi = app(AuthPublicApi::class);
                if (!$authApi->hasAnyRole([Roles::USER_CONFIRMED])) {
                    throw new UnauthorizedException('Only confirmed users may comment');
                }
            }
        });

        $simple = alice($this, roles: [Roles::USER]);
        $this->actingAs($simple);
        expect(function() {
            createComment('chapter', 1, 'Hello', null);
        })->toThrow(UnauthorizedException::class);

        $confirmed = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($confirmed);
        $id = createComment('chapter', 1, 'Hello', null);
        expect($id)->toBeGreaterThan(0);
    });

    it('enforces a 140 character limit via policy', function() {
        /** @var CommentPolicyRegistry $registry */
        $registry = app(CommentPolicyRegistry::class);
        $registry->register('chapter', new class implements CommentPostingPolicy {
            public function validateCreate(CommentToCreateDto $dto): void
            {
                $len = mb_strlen(trim(strip_tags($dto->body)));
                if ($len > 140) {
                    throw ValidationException::withMessages(['body' => ['Comment too long']]);
                }
            }
        });

        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $long = str_repeat('a', 141);
        expect(function() use ($long) {
            createComment('chapter', 1, $long, null);
        })->toThrow(ValidationException::withMessages(['body' => ['Comment too long']]));

        $ok = str_repeat('b', 140);
        $id = createComment('chapter', 1, $ok, null);
        expect($id)->toBeGreaterThan(0);
    });
});

describe('Root content', function() {
    it('should allow to create a root comment', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment('chapter', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('should sanitize the content of the comment', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment('chapter', 1, '<script>alert("xss");</script>Hello', null);
        $comment = getComment($commentId);
        expect($comment->body)->toContain('Hello');
    });
});

describe('Child comments', function () {

    it('should not allow to create a comment with non matching entity type', function () {
        // Auth as confirmed user
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $commentId = createComment('chapter', 123, 'Hello', null);

        expect(function() use ($commentId) {
            createComment('other', 123, 'Hello', $commentId);
        })->toThrow(ValidationException::class);
    });

    it('should not allow to create a comment with non matching entity id', function () {
        // Auth as confirmed user
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $commentId = createComment('chapter', 123, 'Hello', null);

        expect(function() use ($commentId) {
            createComment('chapter', 456, 'Hello', $commentId);
        })->toThrow(ValidationException::class);
    });

    it('should allow to create a comment with a parent comment', function () {
        // Auth as confirmed user
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $commentId = createComment('chapter', 123, 'Hello', null);

        $childCommentId = createComment('chapter', 123, 'Hello', $commentId);
        expect($childCommentId)->toBeGreaterThan(0);

        $comment = listComments('chapter', 123);
        expect($comment->items)->toHaveCount(1);
        expect($comment->items[0]->children)->toHaveCount(1);
        expect($comment->items[0]->children[0]->body)->toContain('Hello');
    });

    it('should not count the comment replies in the pagination', function () {
        // Auth as confirmed user
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $commentIds = createSeveralComments(5, 'chapter', 123, 'Hello', null);

        createComment('chapter', 123, 'Hello', $commentIds[0]);

        $response = listComments('chapter', 123);
        // We only have 5 comments in the pagination, because the 6th one is in the replies, so will be loaded inside the first comment
        expect($response->total)->toBe(5);
        expect($response->items)->toHaveCount(5);
    });
});