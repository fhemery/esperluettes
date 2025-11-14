<?php

namespace App\Domains\StoryRef\Public\Contracts;

class TypeWriteDto
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly bool $is_active,
        public readonly ?int $order,
    ) {}

    /**
     * @return array{name:string,slug:string,is_active:bool,order:?int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'order' => $this->order,
        ];
    }
}
