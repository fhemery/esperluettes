<?php

namespace App\Domains\Story\Private\Support;

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Public\Contracts\StoryQueryReadStatus;

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
         * @var array<int, int> Filtering by author user IDs (OR semantics)
         */
        public array $authorIds = [],

        /**
         * @var array<int,int> Audience IDs to filter by (multi-select)
         */
        public array $audienceIds = [],
        /**
         * @var array<int,int> Genre IDs to filter by (multi-select, AND semantics)
         */
        public array $genreIds = [],
        /**
         * @var array<int,int> Story Type IDs to filter by (multi-select, OR semantics)
         */
        public array $typeIds = [],
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

        /**
         * @var array<int,int> IDs of stories to include (multi-select)
         */
        public ?array $onlyStoryIds = null,

        /**
         * @var StoryQueryReadStatus filters stories by read status
         * Unread means at least one unread chapter. The rest is considered Read.
         */
        public StoryQueryReadStatus $readStatus = StoryQueryReadStatus::All,
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

        // Normalize story ids
        $this->onlyStoryIds = $this->onlyStoryIds ? array_values(array_unique(array_map('intval', $this->onlyStoryIds))) : null;
    }
}
