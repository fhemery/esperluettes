<?php

namespace App\Domains\Calendar\Private\Requests\Admin;

use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            // Media image-field payload: a new upload (file) xor a reused/kept path.
            'image' => ['nullable', 'array'],
            'image.file' => ['nullable', 'image', 'max:2048'],
            'image.path' => ['nullable', 'string', 'max:1024'],
            'role_restrictions' => ['nullable', 'array'],
            'role_restrictions.*' => ['nullable', 'string'],
            'requires_subscription' => ['nullable', 'boolean'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'preview_starts_at' => ['nullable', 'date'],
            'active_starts_at' => ['nullable', 'date'],
            'active_ends_at' => ['nullable', 'date'],
            'archived_at' => ['nullable', 'date'],
        ];

        if ($this->isMethod('POST')) {
            $rules['activity_type'] = ['required', 'string'];
        }

        return array_merge($rules, $this->pluginRules());
    }

    /**
     * Rules contributed by the selected activity type, if any.
     * An unknown or absent type contributes nothing — the type itself is
     * validated against the registry by ValidatesActivityPayload.
     *
     * @return array<string,mixed>
     */
    private function pluginRules(): array
    {
        $type = $this->selectedActivityType();
        if ($type === null) {
            return [];
        }

        $registry = app(CalendarRegistry::class);

        return $registry->has($type) ? $registry->get($type)->configRules() : [];
    }

    /**
     * On create the type comes from the submitted select; on update the form
     * does not resubmit it, so it comes from the stored activity.
     */
    private function selectedActivityType(): ?string
    {
        if ($this->isMethod('POST')) {
            $type = $this->input('activity_type');

            return is_string($type) && $type !== '' ? $type : null;
        }

        $activity = $this->route('activity');

        return $activity instanceof Activity ? $activity->activity_type : null;
    }
}
