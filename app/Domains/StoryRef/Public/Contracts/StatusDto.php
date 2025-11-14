<?php

namespace App\Domains\StoryRef\Public\Contracts;

use App\Domains\StoryRef\Private\Models\StoryRefStatus;

class StatusDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $is_active,
        public readonly ?int $order,
    ) {}

    public static function fromModel(StoryRefStatus $model): self
    {
        return new self(
            id: (int) $model->id,
            slug: (string) $model->slug,
            name: (string) $model->name,
            description: $model->description !== null ? (string) $model->description : null,
            is_active: (bool) $model->is_active,
            order: $model->order !== null ? (int) $model->order : null,
        );
    }

    /**
     * @return array{id:int,slug:string,name:string,description:?string,is_active:bool,order:?int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'order' => $this->order,
        ];
    }
}
