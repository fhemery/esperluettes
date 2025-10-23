<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Requests;

use App\Domains\Calendar\Public\Api\CalendarRegistry;
use App\Domains\Calendar\Public\Contracts\ActivityToUpdateDto;
use Illuminate\Validation\ValidationException;
use App\Domains\Calendar\Private\Requests\Concerns\ValidatesActivityPayload;

class ActivityUpdateRequest
{
    use ValidatesActivityPayload;
    /**
     * Validate incoming ActivityToUpdateDto and return normalized array for persistence.
     * Full replace semantics.
     *
     * @return array<string,mixed>
     * @throws ValidationException
     */
    public function validate(ActivityToUpdateDto $dto, CalendarRegistry $registry): array
    {
        $payload = [
            'name' => $dto->name,
            'activity_type' => $dto->activity_type,
            'description' => $dto->description,
            'image_path' => $dto->image_path,
            'role_restrictions' => $dto->role_restrictions,
            'requires_subscription' => (bool) ($dto->requires_subscription ?? false),
            'max_participants' => $dto->max_participants,
            'preview_starts_at' => $dto->preview_starts_at,
            'active_starts_at' => $dto->active_starts_at,
            'active_ends_at' => $dto->active_ends_at,
            'archived_at' => $dto->archived_at,
        ];

        return $this->validateAndNormalize($registry, $payload);
    }
}
