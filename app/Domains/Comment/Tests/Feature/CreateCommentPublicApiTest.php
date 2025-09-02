<?php

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Comment\PublicApi\CommentPublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Ensure container can resolve the Public API.
    $this->api = app(CommentPublicApi::class);
});

describe('Access', function() {
    it('should return 401 if user is not Logged', function() {
        expect(function() {
            createComment($this->api, 'chapter', 1, 'Hello', null);
        })->toThrow(UnauthorizedException::class);
    });

    it('should return 401 is user is not verified', function() {
        expect(function() {
            $user = alice($this, roles: [], isVerified: false);
            $this->actingAs($user);
            createComment($this->api, 'chapter', 1, 'Hello', null);
        })->toThrow(UnauthorizedException::class);
    });

    it('should work for users on probation (simple user role)', function() {
        $user = alice($this, roles:[Roles::USER]);
        $this->actingAs($user);
        $commentId = createComment($this->api, 'chapter', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('should work for confirmed users (user_confirmed role)', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment($this->api, 'chapter', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });
});

describe('Root content', function() {
    it('should allow to create a root comment', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment($this->api, 'chapter', 1, 'Hello', null);
        expect($commentId)->toBeGreaterThan(0);
    });

    it('should sanitize the content of the comment', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $commentId = createComment($this->api, 'chapter', 1, '<script>alert("xss");</script>Hello', null);
        $comment = getComment($this->api, $commentId);
        expect($comment->body)->toContain('Hello');
    });
});
