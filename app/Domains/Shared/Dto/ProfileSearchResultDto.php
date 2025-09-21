<?php

namespace App\Domains\Shared\Dto;

class ProfileSearchResultDto
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $display_name,
        public readonly string $slug,
        public readonly ?string $avatar_url,
        public readonly string $url
    ) {
    }
}
