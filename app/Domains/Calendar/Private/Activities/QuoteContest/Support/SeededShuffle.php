<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Support;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The order a reader sees a category's entries in (decision #22).
 *
 * Seeded on `(reader, category)`, so it is deterministic per reader and stable
 * across reloads — the entry someone was considering never moves — while no two
 * readers share a first position. It costs no stored column and no extra query:
 * the seed is derived from ids the caller already holds.
 *
 * The randomizer carries its own engine rather than reseeding the global one:
 * `shuffle()` would leave every later `mt_rand()` of the request running off a
 * predictable seed.
 */
final class SeededShuffle
{
    /**
     * @template T
     * @param array<int, T> $items
     * @return array<int, T>
     */
    public static function order(array $items, int $userId, int $categoryId): array
    {
        if (count($items) < 2) {
            return array_values($items);
        }

        $seed = crc32($userId . ':' . $categoryId);

        return (new Randomizer(new Mt19937($seed)))->shuffleArray(array_values($items));
    }
}
