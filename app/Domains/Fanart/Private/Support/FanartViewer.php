<?php

namespace App\Domains\Fanart\Private\Support;

/** Who is looking, as far as fanart listing queries are concerned (see Fanart::scopeVisibleTo). */
final class FanartViewer
{
    /**
     * @param bool $seesAll Authors of the story and staff: pending items and unpublished chapters included.
     * @param int[] $publishedChapterIds Chapters whose published fanarts a reader may see (ignored when $seesAll).
     * @param bool $includeOwnPending The viewer's own pending items, for their own profile tab.
     */
    public function __construct(
        public readonly int $userId,
        public readonly bool $seesAll,
        public readonly array $publishedChapterIds,
        public readonly bool $includeOwnPending = false,
    ) {
    }
}
