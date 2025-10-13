<?php

use App\Domains\Comment\Public\Api\CommentPublicApi;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

describe('CommentPublicApi: Count', function () {
    describe('getNbRootCommentsFor', function () {
        it('returns correct root counts per target', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $entityType = 'chapter';
            $chapter1 = 1; // ids are arbitrary for the purpose of the test
            $chapter2 = 2;
            $chapter3 = 3;

            // Create two users (authors/readers). We do not need to stay logged-in for repository inserts.
            $alice = alice($this); // confirmed user
            $bob   = bob($this);

            // Seed comments:
            // chapter1: 2 roots (alice, bob) + a reply (bob to alice)
            $this->actingAs($alice);
            $root1_c1 = createComment($entityType, $chapter1, generateDummyText(150));
            $this->actingAs($bob);
            $root2_c1 = createComment($entityType, $chapter1, generateDummyText(150));

            createComment($entityType, $chapter1, 'reply to A1', $root1_c1);

            // chapter2: 1 root (alice)
            $this->actingAs($alice);
            $root1_c2 = createComment($entityType, $chapter2, generateDummyText(150));

            // chapter3: none

            $counts = $api->getNbRootCommentsFor($entityType, [$chapter1, $chapter2, $chapter3]);

            expect($counts[$chapter1] ?? 0)->toBe(2)
                ->and($counts[$chapter2] ?? 0)->toBe(1)
                ->and($counts[$chapter3] ?? 0)->toBe(0);
        });
    });

    describe('getNbRootComments', function () {
        it('returns correct comment counts per target', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $entityType = 'default';
            $commentId = createComment($entityType, 1, generateDummyText(10));
            createComment($entityType, 1, generateDummyText(10));
            createComment($entityType, 1, generateDummyText(10), $commentId); // This is a reply

            createComment($entityType, 2, generateDummyText(10)); // This comment is on other entity

            $count = $api->getNbRootComments($entityType, 1);
            expect($count)->toBe(2);
        });

        it('returns filters by author if requested', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $entityType = 'default';
            $commentId = createComment($entityType, 1, generateDummyText(10));
            createComment($entityType, 1, generateDummyText(10));
            createComment($entityType, 1, generateDummyText(10), $commentId); // This is a reply

            $this->actingAs(bob($this));
            createComment($entityType, 2, generateDummyText(10)); // This comment is on other entity

            $this->actingAs(alice($this));
            $count = $api->getNbRootComments($entityType, 1, $alice->id);
            expect($count)->toBe(2);
        });
    });

    describe('countRootCommentsByUser', function () {
        it('returns correct comment counts per target', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $entityType = 'default';
            $commentId = createComment($entityType, 1, generateDummyText(10));
            createComment($entityType, 1, generateDummyText(10));
            createComment($entityType, 1, generateDummyText(10), $commentId); // This is a reply

            createComment($entityType, 2, generateDummyText(10)); // This comment is on other entity

            $count = $api->countRootCommentsByUser($entityType, $alice->id);
            expect($count)->toBe(2);
        });

        it('counts user root comments across different chapters (one on each)', function () {
            /** @var CommentPublicApi $api */
            $api = app(CommentPublicApi::class);

            $alice = alice($this);
            $this->actingAs($alice);

            $entityType = 'chapter';
            // Use arbitrary chapter ids (we don't need actual chapter models here)
            $chapter1 = 101;
            $chapter2 = 202;

            // One root comment on each distinct chapter
            createComment($entityType, $chapter1, generateDummyText(150));
            createComment($entityType, $chapter2, generateDummyText(150));

            $count = $api->countRootCommentsByUser($entityType, $alice->id);
            expect($count)->toBe(2);
        });
    });
});
