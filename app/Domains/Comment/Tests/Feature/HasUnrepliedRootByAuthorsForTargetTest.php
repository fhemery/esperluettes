<?php

use App\Domains\Comment\Public\Api\CommentPublicApi;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('detects roots without replies from story authors', function () {
    /** @var CommentPublicApi $api */
    $api = app(CommentPublicApi::class);

    $entityType = 'chapter';
    $chapter1 = 11;
    $chapter2 = 12;

    // Authors of the story are Alice and Bob
    $alice = alice($this);
    $bob   = bob($this);
    $carol = carol($this);
    $authorIds = [$alice->id];

    // chapter1:
    // - root R1 by a reader (id 9999), with NO replies from authors -> should flag true
    // - root R2 by a reader, with a reply by Alice -> that root is covered
    $this->actingAs($carol);
    $r1 = createComment($entityType, $chapter1, generateDummyText(150));
    $this->actingAs($bob);
    $r2 = createComment($entityType, $chapter1, generateDummyText(150));
    $this->actingAs($alice);
    createComment($entityType, $chapter1, 'author reply to r2', $r2);

    // chapter2:
    // - root R3 by reader, with reply by Bob -> all roots replied by authors -> should flag false
    $this->actingAs($carol);
    $r3 = createComment($entityType, $chapter2, generateDummyText(150));
    $this->actingAs($alice);
    createComment($entityType, $chapter2, 'author reply to r3', $r3);

    $flags = $api->getHasUnrepliedRootsByAuthorsForTargets($entityType, [$chapter1, $chapter2], $authorIds);

    expect($flags[$chapter1] ?? false)->toBeTrue()
        ->and($flags[$chapter2] ?? true)->toBeFalse();
});
