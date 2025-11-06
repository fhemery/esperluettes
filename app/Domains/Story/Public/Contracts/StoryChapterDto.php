<?php

namespace App\Domains\Story\Public\Contracts;

class StoryChapterDto
{
    public function __construct(
        public int $id,
        public string $title,
        public int $sort_order,
        public string $status,
        public ?string $read_at_iso = null,
        public bool $is_read = false,
    ) {
    }
}
