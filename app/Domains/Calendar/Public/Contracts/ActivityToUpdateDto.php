<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Contracts;

use Carbon\CarbonInterface;

class ActivityToUpdateDto
{
    public function __construct(
        public string $name,
        public string $activity_type,
        public ?string $description = null,
        public ?string $image_path = null,
        /** @var array<int,string>|null */
        public ?array $role_restrictions = null,
        public ?bool $requires_subscription = false,
        public ?int $max_participants = null,
        public ?CarbonInterface $preview_starts_at = null,
        public ?CarbonInterface $active_starts_at = null,
        public ?CarbonInterface $active_ends_at = null,
        public ?CarbonInterface $archived_at = null,
    ) {}
}
