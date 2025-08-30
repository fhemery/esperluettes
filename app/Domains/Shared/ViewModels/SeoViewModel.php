<?php

namespace App\Domains\Shared\ViewModels;

class SeoViewModel
{
    public function __construct(
        public readonly string $title,
        public readonly string $coverImage,
    ) {}
}
