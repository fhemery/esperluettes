<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Support\SeededShuffle;

/**
 * The ballot's order is a pure function of (reader, category) — no stored
 * column, no query, no clock. That is exactly what makes it unit-testable, and
 * the second of the feature's only two unit tests (architecture §6).
 */

it('gives the same reader the same order for the same category, every time', function () {
    $entries = [11, 22, 33, 44, 55, 66, 77, 88];

    $first = SeededShuffle::order($entries, userId: 1, categoryId: 7);
    $second = SeededShuffle::order($entries, userId: 1, categoryId: 7);

    expect($second)->toBe($first);
});

it('gives two readers different orders in the same category', function () {
    // Decision #22: no positional advantage for an early submitter.
    $entries = [11, 22, 33, 44, 55, 66, 77, 88];

    $alice = SeededShuffle::order($entries, userId: 1, categoryId: 7);
    $bob = SeededShuffle::order($entries, userId: 2, categoryId: 7);

    expect($bob)->not->toBe($alice);
});

it('gives one reader different orders in two categories', function () {
    $entries = [11, 22, 33, 44, 55, 66, 77, 88];

    $funniest = SeededShuffle::order($entries, userId: 1, categoryId: 7);
    $saddest = SeededShuffle::order($entries, userId: 1, categoryId: 8);

    expect($saddest)->not->toBe($funniest);
});

it('keeps every item, exactly once', function () {
    $entries = [11, 22, 33, 44, 55, 66, 77, 88];

    $shuffled = SeededShuffle::order($entries, userId: 3, categoryId: 9);

    expect($shuffled)->toHaveCount(count($entries));

    sort($shuffled);
    expect($shuffled)->toBe($entries);
});

it('handles the empty and single-entry cases', function () {
    expect(SeededShuffle::order([], userId: 1, categoryId: 7))->toBe([])
        ->and(SeededShuffle::order([42], userId: 1, categoryId: 7))->toBe([42]);
});
