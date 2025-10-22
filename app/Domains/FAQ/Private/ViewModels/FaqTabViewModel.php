<?php

namespace App\Domains\FAQ\Private\ViewModels;

class FaqTabViewModel
{
    public function __construct(
        private readonly string $key,
        private readonly string $label,
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return array{key:string,label:string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
        ];
    }
}
