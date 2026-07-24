<?php

declare(strict_types=1);

namespace App\Domains\Media\Public\Contracts;

/**
 * Implemented by any domain that stores managed image paths.
 *
 * The Media garbage collector unions every provider's paths to know which
 * files are still in use; countUsages() sums occurrences across providers.
 * A domain that stores image paths but registers no provider makes its files
 * invisible to GC — the GC guards against this (see MediaGcCommand).
 */
interface MediaUsageProvider
{
    /**
     * Every managed image path this domain currently references, one entry
     * per occurrence (duplicates included, so counts are accurate).
     *
     * @return iterable<string>
     */
    public function usedPaths(): iterable;
}
