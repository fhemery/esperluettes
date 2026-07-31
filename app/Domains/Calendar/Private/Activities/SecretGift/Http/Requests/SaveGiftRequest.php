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
        if ($this->has('gift_sound_remove')) {
            $value = $this->input('gift_sound_remove');
            $this->merge([
                'gift_sound_remove' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'gift_text' => ['nullable', 'string', 'max:65535'],
            // <x-media::image-field> shape: an empty path with no file means "removed".
            'gift_image.path' => ['nullable', 'string', 'max:255'],
            'gift_image.file' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'gift_sound' => ['nullable', 'file', 'mimes:mp3', 'max:10240'],
            'gift_sound_remove' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'gift_text.max' => __('secret-gift::secret-gift.validation.gift_text_max'),
            'gift_image.file.mimes' => __('secret-gift::secret-gift.validation.gift_image_mimes'),
            'gift_image.file.max' => __('secret-gift::secret-gift.validation.gift_image_max'),
            'gift_sound.mimes' => __('secret-gift::secret-gift.validation.gift_sound_mimes'),
            'gift_sound.max' => __('secret-gift::secret-gift.validation.gift_sound_max'),
        ];
    }
}
