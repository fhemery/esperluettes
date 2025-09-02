<?php

use App\Domains\Comment\PublicApi\CommentPublicApi;
use App\Domains\Comment\Contracts\CommentListDto;
use App\Domains\Comment\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Ensure container can resolve the Public API.
    $this->api = app(CommentPublicApi::class);
});

it('returns an empty list when no comment are added', function () {
    // Given a chapter-like target (entityType/id are placeholders until Story adapter is wired)
    $entityType = 'chapter';
    $entityId = 1;

    $result = $this->api->getFor($entityType, $entityId, page: 1, perPage: 20);

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

    $this->api->create($entityType, $entityId, 1, 'Hello');

    $result = $this->api->getFor($entityType, $entityId, page: 1, perPage: 20);

    expect($result)->toBeInstanceOf(CommentListDto::class)
        ->and($result->entityType)->toBe($entityType)
        ->and($result->entityId)->toBe((string) $entityId)
        ->and($result->page)->toBe(1)
        ->and($result->perPage)->toBe(20)
        ->and($result->total)->toBe(1)
        ->and($result->items)->toBeArray()
        ->and($result->items[0]->body)->toBe('Hello');
});

it('lists root comments by descending creation date', function () {
    $entityType = 'chapter';
    $entityId = 1;
    $authorId = 1;
    
    // Create three comments
    $comment1Id = $this->api->create($entityType, $entityId, $authorId, 'Hello');
    $comment2Id = $this->api->create($entityType, $entityId, $authorId, 'World');
    $comment3Id = $this->api->create($entityType, $entityId, $authorId, 'Universe');
    
    // We need to go update the created_at timestamp for each comment to make sure the sorting works
    Comment::query()->where('id', $comment1Id)->update(['created_at' => now()->subMinutes(10)]);
    Comment::query()->where('id', $comment2Id)->update(['created_at' => now()->subMinutes(5)]);
    Comment::query()->where('id', $comment3Id)->update(['created_at' => now()]);
    
    // Act: get the comments
    $result = $this->api->getFor($entityType, $entityId);

    // Assert
    expect($result->items)->toBeArray()
        ->and($result->items[0]->id)->toBe($comment3Id)
        ->and($result->items[1]->id)->toBe($comment2Id)
        ->and($result->items[2]->id)->toBe($comment1Id);
});