<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Contracts;

use Carbon\CarbonInterface;
use App\Domains\Calendar\Private\Models\Activity;

class ActivityDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $image_path,
        /** @var array<int,string> */
        public array $role_restrictions,
        public bool $requires_subscription,
        public ?int $max_participants,
        public ?CarbonInterface $preview_starts_at,
        public ?CarbonInterface $active_starts_at,
        public ?CarbonInterface $active_ends_at,
        public ?CarbonInterface $archived_at,
        public string $activity_type,
        public string $state,
    ) {}

    public static function fromModel(Activity $a): self
    {
        return new self(
            id: $a->id,
            name: $a->name,
            slug: $a->slug,
            description: $a->description,
            image_path: $a->image_path,
            role_restrictions: $a->role_restrictions ?? [],
            requires_subscription: (bool) $a->requires_subscription,
            max_participants: $a->max_participants,
            preview_starts_at: $a->preview_starts_at,
            active_starts_at: $a->active_starts_at,
            active_ends_at: $a->active_ends_at,
            archived_at: $a->archived_at,
            activity_type: $a->activity_type,
            state: (string) $a->state,
        );
    }
}
