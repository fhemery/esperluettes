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
        public ?int $userId = null,
        public ?int $typeId = null,
        /**
         * @var array<int,int> Audience IDs to filter by (multi-select)
         */
        public array $audienceIds = [],
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
    }
}
