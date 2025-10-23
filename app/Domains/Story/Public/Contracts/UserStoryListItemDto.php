<?php

declare(strict_types=1);

namespace App\Domains\Story\Public\Contracts;

final class UserStoryListItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
    ) {}
}
