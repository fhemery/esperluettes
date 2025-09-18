<?php

namespace App\Domains\Story\Support;

use App\Domains\Story\Models\Story;

class StoryFilterAndPagination
{
    public function __construct(
        public int $page,
        public int $perPage = 24,
        /**
         * @var array<int,string>
         */
        public array $visibilities = [Story::VIS_PUBLIC],
        /**
         * Preferred alias for filtering by author user ID. If provided, it takes precedence over userId.
         */
        public ?int $authorId = null,
        public ?int $typeId = null,
        /**
         * @var array<int,int> Audience IDs to filter by (multi-select)
         */
        public array $audienceIds = [],
        /**
         * @var array<int,int> Genre IDs to filter by (multi-select, AND semantics)
         */
        public array $genreIds = [],
        /**
         * @var array<int,int> Trigger Warning IDs to EXCLUDE (multi-select, OR semantics)
         */
        public array $excludeTriggerWarningIds = [],
        /**
         * When true (default), only include stories that have at least one published chapter
         * and sort primarily by last_chapter_published_at DESC.
         * When false, include stories regardless of chapter presence (used in profile pages for owner).
         */
        public bool $requirePublishedChapter = true,
        /**
         * When true, only include stories explicitly marked as having no trigger warnings.
         */
        public bool $noTwOnly = false,
    ) {
        // Normalize visibilities: ensure values and not empty
        $this->visibilities = array_values($this->visibilities);
        if (empty($this->visibilities)) {
            $this->visibilities = [Story::VIS_PUBLIC];
        }

        // Safety on page/perPage
        $this->page = max(1, (int) $this->page);
        $this->perPage = max(1, (int) $this->perPage);

        // Normalize audience ids
        $this->audienceIds = array_values(array_unique(array_map('intval', $this->audienceIds)));

        // Normalize genre ids
        $this->genreIds = array_values(array_unique(array_map('intval', $this->genreIds)));

        // Normalize excluded trigger warning ids
        $this->excludeTriggerWarningIds = array_values(array_unique(array_map('intval', $this->excludeTriggerWarningIds)));

        // Normalize booleans
        $this->noTwOnly = (bool) $this->noTwOnly;
    }
}
