<?php

namespace App\Domains\StoryRef\Public\Contracts;

class StoryRefFilterDto
{
    public function __construct(
        public readonly bool $activeOnly = true,
    ) {}
}
