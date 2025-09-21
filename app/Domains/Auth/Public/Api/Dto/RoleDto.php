<?php

namespace App\Domains\Auth\Public\Api\Dto;

use App\Domains\Auth\Private\Models\Role;

class RoleDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: (int) $role->id,
            name: (string) $role->name,
            slug: (string) $role->slug,
            description: $role->description,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}
