<?php

namespace App\Domains\Shared\Dto;

class BreadcrumbDto
{
    public string $label;
    public ?string $url;
    public bool $active;
    public ?string $icon;

    public function __construct(string $label, ?string $url = null, bool $active = false, ?string $icon = null)
    {
        $this->label = $label;
        $this->url = $url;
        $this->active = $active;
        $this->icon = $icon;
    }
}
