<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Public\Contracts\StoryQueryReadStatus;

class StoryQueryFilterDto
{
    public function __construct(
        public array $onlyStoryIds = [],
        public StoryQueryReadStatus $readStatus = StoryQueryReadStatus::All,
        public array $filterByGenreIds = [],
        public array $visibilities = [StoryVisibility::PUBLIC, StoryVisibility::COMMUNITY, StoryVisibility::PRIVATE],
    ) {
    }
}
