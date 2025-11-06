<?php

namespace App\Domains\Story\Public\Contracts;

class StoryQueryPaginationDto
{
    public function __construct(
        public int $page = 1,
        public int $pageSize = 10,
    ) {
    }
}
