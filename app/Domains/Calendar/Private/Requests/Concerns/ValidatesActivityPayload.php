<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Requests\Concerns;

use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

trait ValidatesActivityPayload
{
    /**
     * Validate and normalize an activity payload for create/update.
     * - Applies required_trimmed rules (with defensive fallback)
     * - Validates activity_type against registry
     * - Validates date ordering (allow nulls and equal boundaries)
     * - Trims name and activity_type
     * - Sanitizes description with Purifier 'admin-content'
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     * @throws ValidationException
     */
    protected function validateAndNormalize(CalendarRegistry $registry, array $payload): array
    {
        $v = Validator::make($payload, [
            'name' => ['required_trimmed'],
            'activity_type' => ['required_trimmed'],
        ]);

        $v->after(function ($validator) use ($payload, $registry) {
            // Defensive enforcement for required_trimmed
            if (!is_string($payload['name'] ?? null) || trim((string)$payload['name']) === '') {
                $validator->errors()->add('name', trans('validation.required'));
            }
            if (!is_string($payload['activity_type'] ?? null) || trim((string)$payload['activity_type']) === '') {
                $validator->errors()->add('activity_type', trans('validation.required'));
            }

            // Activity type must exist in registry
            $type = is_string($payload['activity_type'] ?? null) ? trim($payload['activity_type']) : '';
            if ($type !== '' && ! $registry->has($type)) {
                $validator->errors()->add('activity_type', __('calendar::calendar.validation.activity_type.unknown'));
            }

            // Date order validation (allow nulls and equal boundaries)
            $p  = $payload['preview_starts_at'] ?? null;
            $as = $payload['active_starts_at'] ?? null;
            $ae = $payload['active_ends_at'] ?? null;
            $ar = $payload['archived_at'] ?? null;

            if ($p && $as && $as->lt($p)) {
                $validator->errors()->add('active_starts_at', __('calendar::calendar.validation.dates.active_starts_before_preview'));
            }
            if ($as && $ae && $ae->lt($as)) {
                $validator->errors()->add('active_ends_at', __('calendar::calendar.validation.dates.active_ends_before_start'));
            }
            if ($ae && $ar && $ar->lt($ae)) {
                $validator->errors()->add('archived_at', __('calendar::calendar.validation.dates.archived_before_end'));
            }
        });

        $validated = $v->validate();

        // Normalize back onto full payload
        $payload['name'] = trim((string) $validated['name']);
        $payload['activity_type'] = trim((string) $validated['activity_type']);
        if (array_key_exists('description', $payload) && $payload['description'] !== null) {
            $payload['description'] = Purifier::clean((string) $payload['description'], 'admin-content');
        }

        return $payload;
    }
}
