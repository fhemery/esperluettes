<?php

namespace App\Domains\Shared\Dto;

class FullProfileDto
{
    /**
     * @var array<RoleDto>
     */
    public function __construct(
        public int $userId,
        public string $displayName,
        public string $slug,
        public string $avatarUrl,
        public string $joinDateIso,
        public array $roles,
    ) {
    }
}
