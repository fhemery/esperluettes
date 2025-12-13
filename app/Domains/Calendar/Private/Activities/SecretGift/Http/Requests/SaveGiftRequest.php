<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Convert string "true"/"false" from Alpine x-model to actual boolean
        if ($this->has('gift_image_remove')) {
            $value = $this->input('gift_image_remove');
            $this->merge([
                'gift_image_remove' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'gift_text' => ['nullable', 'string', 'max:65535'],
            'gift_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'gift_image_remove' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'gift_text.max' => __('secret-gift::secret-gift.validation.gift_text_max'),
            'gift_image.mimes' => __('secret-gift::secret-gift.validation.gift_image_mimes'),
            'gift_image.max' => __('secret-gift::secret-gift.validation.gift_image_max'),
        ];
    }
}
