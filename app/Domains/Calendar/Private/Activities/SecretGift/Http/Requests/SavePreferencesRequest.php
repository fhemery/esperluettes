<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The single payload behind joining and editing preferences: the rich-text
 * preferences body. Authorization is the controller's job — it depends on the
 * activity's registration window and role restrictions, not on the payload.
 */
class SavePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferences.max' => __('secret-gift::secret-gift.validation.preferences_max'),
        ];
    }
}
