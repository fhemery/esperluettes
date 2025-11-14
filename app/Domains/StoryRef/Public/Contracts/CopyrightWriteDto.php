<?php

namespace App\Domains\StoryRef\Public\Contracts;

class CopyrightWriteDto
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $is_active,
        public readonly ?int $order,
    ) {}

    /**
     * @return array{name:string,slug:string,description:?string,is_active:bool,order:?int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'order' => $this->order,
        ];
    }
}
