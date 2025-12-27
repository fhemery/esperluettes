<?php

namespace App\Domains\Story\Private\ViewModels;

class ProfileCommentsStoryViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly int $commentCount,
    ) {}
}
