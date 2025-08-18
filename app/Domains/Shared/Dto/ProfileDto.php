<?php

namespace App\Domains\Shared\Dto;

class ProfileDto
{
    public function __construct(
        public int $user_id,
        public string $display_name,
        public string $slug,
        public string $avatar_url,
    ) {
    }
}
