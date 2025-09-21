<?php

declare(strict_types=1);

use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Comment\Public\Api\Contracts\CommentToCreateDto;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Shared\Support\WordCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

it('emits Comment.Posted with a CommentSnapshot for a root comment', function () {
    $user = alice($this, roles: [Roles::USER_CONFIRMED]);
    $this->actingAs($user);

    /** @var CommentPublicApi $api */
    $api = app(CommentPublicApi::class);

    $entityType = 'default';
    $entityId = 42;
    $body = '<p>' . str_repeat('hello ', 10) . '</p>';

    // Create root comment
    $commentId = $api->create(new CommentToCreateDto($entityType, $entityId, $body, null));

    /** @var CommentPosted|null $event */
    $event = latestEventOf(CommentPosted::name(), CommentPosted::class);
    expect($event)->not->toBeNull();

    $c = $event->comment;
    expect($c->commentId)->toBe($commentId);
    expect($c->entityType)->toBe($entityType);
    expect($c->entityId)->toBe($entityId);
    expect($c->authorId)->toBe($user->id);
    expect($c->isReply)->toBeFalse();
    expect($c->parentCommentId)->toBeNull();

    // Counts
    $expectedWords = WordCounter::count($body);
    $expectedChars = mb_strlen(strip_tags($body));
    expect($c->wordCount)->toBe($expectedWords);
    expect($c->charCount)->toBe($expectedChars);
});

it('emits Comment.Posted with a CommentSnapshot for a reply comment', function () {
    $user = alice($this, roles: [Roles::USER_CONFIRMED]);
    $this->actingAs($user);

    /** @var CommentPublicApi $api */
    $api = app(CommentPublicApi::class);

    $entityType = 'default';
    $entityId = 43;
    $rootBody = '<p>' . str_repeat('root ', 20) . '</p>';
    $replyBody = '<p>' . str_repeat('reply ', 12) . '</p>';

    // Root
    $rootId = $api->create(new CommentToCreateDto($entityType, $entityId, $rootBody, null));

    // Reply
    $replyId = $api->create(new CommentToCreateDto($entityType, $entityId, $replyBody, $rootId));

    /** @var CommentPosted|null $event */
    $event = latestEventOf(CommentPosted::name(), CommentPosted::class);
    expect($event)->not->toBeNull();

    $c = $event->comment;
    expect($c->commentId)->toBe($replyId);
    expect($c->entityType)->toBe($entityType);
    expect($c->entityId)->toBe($entityId);
    expect($c->authorId)->toBe($user->id);
    expect($c->isReply)->toBeTrue();
    expect($c->parentCommentId)->toBe($rootId);

    // Counts
    $expectedWords = WordCounter::count($replyBody);
    $expectedChars = mb_strlen(strip_tags($replyBody));
    expect($c->wordCount)->toBe($expectedWords);
    expect($c->charCount)->toBe($expectedChars);
});
