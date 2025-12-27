<?php

declare(strict_types=1);

use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Api\Contracts\CommentDto;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

describe('CommentPublicApi: Get Root Comments By Author', function () {

    describe('getEntityIdsWithRootCommentsByAuthor', function () {
        it('returns empty array when author has no root comments', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);

            $result = $api->getEntityIdsWithRootCommentsByAuthor('chapter', $alice->id);

            expect($result)->toBe([]);
        });

        it('returns entity IDs where author has root comments', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $chapter1 = 101;
            $chapter2 = 202;
            $chapter3 = 303;

            createComment('chapter', $chapter1, generateDummyText(150));
            createComment('chapter', $chapter2, generateDummyText(150));
            // chapter3 has no comment from alice

            $result = $api->getEntityIdsWithRootCommentsByAuthor('chapter', $alice->id);

            expect($result)->toHaveCount(2)
                ->and($result)->toContain($chapter1)
                ->and($result)->toContain($chapter2)
                ->and($result)->not->toContain($chapter3);
        });

        it('does not return entity IDs where author only has replies', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $bob = bob($this);

            $chapter1 = 101;

            // Bob creates a root comment
            $this->actingAs($bob);
            $rootId = createComment('chapter', $chapter1, generateDummyText(150));

            // Alice only replies to Bob's comment
            $this->actingAs($alice);
            createComment('chapter', $chapter1, generateDummyText(150), $rootId);

            $result = $api->getEntityIdsWithRootCommentsByAuthor('chapter', $alice->id);

            expect($result)->toBe([]);
        });

        it('does not include comments from other authors', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $bob = bob($this);

            $chapter1 = 101;
            $chapter2 = 202;

            // Alice comments on chapter1
            $this->actingAs($alice);
            createComment('chapter', $chapter1, generateDummyText(150));

            // Bob comments on chapter2
            $this->actingAs($bob);
            createComment('chapter', $chapter2, generateDummyText(150));

            $result = $api->getEntityIdsWithRootCommentsByAuthor('chapter', $alice->id);

            expect($result)->toHaveCount(1)
                ->and($result)->toContain($chapter1)
                ->and($result)->not->toContain($chapter2);
        });

        it('filters by entity type', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $chapter1 = 101;
            $story1 = 201;

            createComment('chapter', $chapter1, generateDummyText(150));
            createComment('story', $story1, generateDummyText(150));

            $chapterResult = $api->getEntityIdsWithRootCommentsByAuthor('chapter', $alice->id);
            $storyResult = $api->getEntityIdsWithRootCommentsByAuthor('story', $alice->id);

            expect($chapterResult)->toHaveCount(1)
                ->and($chapterResult)->toContain($chapter1)
                ->and($storyResult)->toHaveCount(1)
                ->and($storyResult)->toContain($story1);
        });
    });

    describe('getRootCommentsByAuthorAndEntities', function () {
        it('returns empty array when no entity IDs provided', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);

            $result = $api->getRootCommentsByAuthorAndEntities('chapter', $alice->id, []);

            expect($result)->toBe([]);
        });

        it('returns empty array when author has no comments on specified entities', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $chapter1 = 101;
            $chapter2 = 202;

            // Alice comments on chapter1
            createComment('chapter', $chapter1, generateDummyText(150));

            // But we ask for chapter2
            $result = $api->getRootCommentsByAuthorAndEntities('chapter', $alice->id, [$chapter2]);

            expect($result)->toBe([]);
        });

        it('returns CommentDto keyed by entity ID', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $chapter1 = 101;
            $chapter2 = 202;

            createComment('chapter', $chapter1, generateDummyText(150));
            createComment('chapter', $chapter2, generateDummyText(150));

            $result = $api->getRootCommentsByAuthorAndEntities('chapter', $alice->id, [$chapter1, $chapter2]);

            expect($result)->toHaveCount(2)
                ->and($result)->toHaveKey($chapter1)
                ->and($result)->toHaveKey($chapter2)
                ->and($result[$chapter1])->toBeInstanceOf(CommentDto::class)
                ->and($result[$chapter2])->toBeInstanceOf(CommentDto::class);
        });

        it('returns only requested entity IDs', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $chapter1 = 101;
            $chapter2 = 202;
            $chapter3 = 303;

            createComment('chapter', $chapter1, generateDummyText(150));
            createComment('chapter', $chapter2, generateDummyText(150));
            createComment('chapter', $chapter3, generateDummyText(150));

            // Only request chapter1 and chapter2
            $result = $api->getRootCommentsByAuthorAndEntities('chapter', $alice->id, [$chapter1, $chapter2]);

            expect($result)->toHaveCount(2)
                ->and($result)->toHaveKey($chapter1)
                ->and($result)->toHaveKey($chapter2)
                ->and($result)->not->toHaveKey($chapter3);
        });

        it('does not return replies', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $bob = bob($this);

            $chapter1 = 101;

            // Bob creates a root comment
            $this->actingAs($bob);
            $rootId = createComment('chapter', $chapter1, generateDummyText(150));

            // Alice only replies
            $this->actingAs($alice);
            createComment('chapter', $chapter1, generateDummyText(150), $rootId);

            $result = $api->getRootCommentsByAuthorAndEntities('chapter', $alice->id, [$chapter1]);

            expect($result)->toBe([]);
        });

        it('returns correct comment body and author profile', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $chapter1 = 101;
            $commentBody = generateDummyText(150);

            createComment('chapter', $chapter1, $commentBody);

            $result = $api->getRootCommentsByAuthorAndEntities('chapter', $alice->id, [$chapter1]);

            expect($result[$chapter1]->authorId)->toBe($alice->id)
                ->and($result[$chapter1]->authorProfile->user_id)->toBe($alice->id)
                ->and($result[$chapter1]->authorProfile->display_name)->toBe('Alice')
                ->and($result[$chapter1]->entityType)->toBe('chapter')
                ->and($result[$chapter1]->entityId)->toBe($chapter1);
        });
    });
});
