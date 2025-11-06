<?php

namespace App\Domains\Story\Public\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoriesPaginationDto
{
    public function __construct(
        public int $current_page,
        public int $per_page,
        public int $total,
        public int $last_page,
    ) {
    }

    public static function from(LengthAwarePaginator $paginator): self
    {
        return new self(
            current_page: (int) $paginator->currentPage(),
            per_page: (int) $paginator->perPage(),
            total: (int) $paginator->total(),
            last_page: (int) $paginator->lastPage(),
        );
    }
}
