<?php

namespace App\Domains\StoryRef\Public\Contracts;

use App\Domains\StoryRef\Private\Models\StoryRefAudience;

class AudienceDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly bool $is_active,
        public readonly ?int $order,
        public readonly ?int $threshold_age,
        public readonly bool $is_mature_audience,
    ) {}

    public static function fromModel(StoryRefAudience $model): self
    {
        return new self(
            id: (int) $model->id,
            slug: (string) $model->slug,
            name: (string) $model->name,
            is_active: (bool) $model->is_active,
            order: $model->order !== null ? (int) $model->order : null,
            threshold_age: $model->threshold_age !== null ? (int) $model->threshold_age : null,
            is_mature_audience: (bool) $model->is_mature_audience,
        );
    }

    /**
     * @return array{id:int,slug:string,name:string,is_active:bool,order:?int,threshold_age:?int,is_mature_audience:bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'threshold_age' => $this->threshold_age,
            'is_mature_audience' => $this->is_mature_audience,
        ];
    }
}
