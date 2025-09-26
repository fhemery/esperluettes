<?php

use App\Domains\Comment\Public\Api\CommentPublicApi;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

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

    $counts = $api->getRootCountsForTargets($entityType, [$chapter1, $chapter2, $chapter3]);

    expect($counts[$chapter1] ?? 0)->toBe(2)
        ->and($counts[$chapter2] ?? 0)->toBe(1)
        ->and($counts[$chapter3] ?? 0)->toBe(0);
});