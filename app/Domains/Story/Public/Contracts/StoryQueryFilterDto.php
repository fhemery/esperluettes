<?php

namespace App\Domains\Story\Public\Contracts;

enum StoryQueryReadStatus: int {
    case All = 0;
    case UnreadOnly = 1;
    case ReadOnly = 2;
}

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
