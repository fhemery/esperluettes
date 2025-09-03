<?php

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

describe('Access', function() {
    it('should return 401 if user is not Logged', function() {
        expect(function() {
            listComments('chapter', 1);
        })->toThrow(UnauthorizedException::class);
    });

    it('should return 401 is user is not verified', function() {
        expect(function() {
            $user = alice($this, roles: [], isVerified: false);
            $this->actingAs($user);
            listComments('chapter', 1);
        })->toThrow(UnauthorizedException::class);
    });

    it('should work for users on probation (simple user role)', function() {
        $user = alice($this, roles:[Roles::USER]);
        $this->actingAs($user);
        $result = listComments('chapter', 1);
        expect($result)->toBeInstanceOf(CommentListDto::class);
    });

    it('should work for confirmed users (user_confirmed role)', function() {
        $user = alice($this, roles:[Roles::USER_CONFIRMED]);
        $this->actingAs($user);
        $result = listComments('chapter', 1);
        expect($result)->toBeInstanceOf(CommentListDto::class);
    });
});

it('returns an empty list when no comment are added', function () {   
    // Given a chapter-like target (entityType/id are placeholders until Story adapter is wired)
    $entityType = 'chapter';
    $entityId = 1;
    $user = alice($this);
    $this->actingAs($user);

    $result = listComments($entityType, $entityId, page: 1, perPage: 20);

    expect($result)->toBeInstanceOf(CommentListDto::class)
        ->and($result->entityType)->toBe($entityType)
        ->and($result->entityId)->toBe((string) $entityId)
        ->and($result->page)->toBe(1)
        ->and($result->perPage)->toBe(20)
        ->and($result->total)->toBe(0)
        ->and($result->items)->toBeEmpty();
});

it('returns a list of comments when some are added', function () {
    $entityType = 'chapter';
    $entityId = 1;
    $alice = alice($this);
    $this->actingAs($alice);

    createComment($entityType, $entityId, 'Hello');

    $result = listComments($entityType, $entityId, page: 1, perPage: 20);

    expect($result)->toBeInstanceOf(CommentListDto::class)
        ->and($result->entityType)->toBe($entityType)
        ->and($result->entityId)->toBe((string) $entityId)
        ->and($result->page)->toBe(1)
        ->and($result->perPage)->toBe(20)
        ->and($result->total)->toBe(1)
        ->and($result->items)->toBeArray()
        ->and($result->items[0]->body)->toContain('Hello');
});

it('lists root comments by descending creation date', function () {
    $entityType = 'chapter';
    $entityId = 1;
    $user = alice($this);
    $this->actingAs($user);
    
    // Create three comments
    $comment1Id = createComment($entityType, $entityId, 'Hello');
    $comment2Id = createComment($entityType, $entityId, 'World');
    $comment3Id = createComment($entityType, $entityId, 'Universe');
    
    // We need to go update the created_at timestamp for each comment to make sure the sorting works
    Comment::query()->where('id', $comment1Id)->update(['created_at' => now()->subMinutes(10)]);
    Comment::query()->where('id', $comment2Id)->update(['created_at' => now()->subMinutes(5)]);
    Comment::query()->where('id', $comment3Id)->update(['created_at' => now()]);
    
    // Act: get the comments
    $result = listComments($entityType, $entityId);

    // Assert
    expect($result->items)->toBeArray()
        ->and($result->items[0]->id)->toBe($comment3Id)
        ->and($result->items[1]->id)->toBe($comment2Id)
        ->and($result->items[2]->id)->toBe($comment1Id);
});