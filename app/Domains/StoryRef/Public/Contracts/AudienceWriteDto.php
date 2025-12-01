<?php

namespace App\Domains\StoryRef\Public\Contracts;

class AudienceWriteDto
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly bool $is_active,
        public readonly ?int $order,
        public readonly ?int $threshold_age,
        public readonly bool $is_mature_audience,
    ) {}

    /**
     * @return array{name:string,slug:string,is_active:bool,order:?int,threshold_age:?int,is_mature_audience:bool}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'order' => $this->order,
            'threshold_age' => $this->threshold_age,
            'is_mature_audience' => $this->is_mature_audience,
        ];
    }
}
