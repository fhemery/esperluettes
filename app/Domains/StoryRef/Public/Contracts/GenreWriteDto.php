<?php

namespace App\Domains\StoryRef\Public\Contracts;

class GenreWriteDto
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $is_active,
        public readonly ?int $order,
    ) {}

    /**
     * @param array{slug?:string,name:string,description?:string|null,is_active?:bool,order?:int|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slug: (string)($data['slug'] ?? ''),
            name: (string)$data['name'],
            description: array_key_exists('description', $data) ? ($data['description'] !== null ? (string)$data['description'] : null) : null,
            is_active: (bool)($data['is_active'] ?? true),
            order: array_key_exists('order', $data) ? ($data['order'] !== null ? (int)$data['order'] : null) : null,
        );
    }
}
