<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GetComment Public API', function () {
    describe('Access', function () {
        it('should return 401 if user is not Logged', function () {
            expect(function () {
                getComment(1);
            })->toThrow(UnauthorizedException::class);
        });

        it('should return 401 is user is not verified', function () {
            expect(function () {
                $user = alice($this, roles: [], isVerified: false);
                $this->actingAs($user);
                getComment(1);
            })->toThrow(UnauthorizedException::class);
        });

        it('should work for users on probation (simple user role)', function () {
            $user = alice($this, roles: [Roles::USER]);
            $this->actingAs($user);

            $id = createComment('default', 1, 'Hello');
            $dto = getComment($id);
            expect($dto)->toBeInstanceOf(CommentDto::class);
        });

        it('should work for confirmed users (user_confirmed role)', function () {
            $user = alice($this, roles: [Roles::USER_CONFIRMED]);
            $this->actingAs($user);

            $id = createComment('default', 1, 'Hello');
            $dto = getComment($id);
            expect($dto)->toBeInstanceOf(CommentDto::class);
        });
    });

    it('returns a full DTO with author profile and permissions', function () {
        $entityType = 'default';
        $entityId = 1;

        // Author and viewer
        $author = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);

        // Create root comment as author
        $this->actingAs($author);
        $commentId = createComment($entityType, $entityId, 'Root body');

        // Read as viewer
        $this->actingAs($viewer);
        $dto = getComment($commentId);

        expect($dto)->toBeInstanceOf(CommentDto::class)
            ->and($dto->entityType)->toBe($entityType)
            ->and($dto->entityId)->toBe($entityId)
            ->and($dto->id)->toBe($commentId)
            ->and($dto->body)->toContain('Root body')
            ->and($dto->authorId)->toBe($author->id)
            ->and($dto->authorProfile->user_id)->toBe($author->id)
            ->and($dto->authorProfile->display_name)->toBe('Alice')
            ->and($dto->authorProfile->slug)->toBe('alice')
            ->and($dto->authorProfile->avatar_url)->toBeString()
            ->and($dto->createdAt)->toBeString();

        // Permissions flags present
        expect($dto->canReply)->toBeTrue();
        expect($dto->canEditOwn)->toBeTrue();
    });

    it('returns children as empty array for single getComment', function () {
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $this->actingAs($user);

        $commentId = createComment('default', 1, 'Root');
        $dto = getComment($commentId);

        // In current behavior, getComment returns a single comment without children
        expect($dto->children)->toBeArray()
            ->and($dto->children)->toBeEmpty();
    });
});
