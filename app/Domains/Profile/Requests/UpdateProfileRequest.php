<?php

namespace App\Domains\Profile\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'x_url' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['nullable', 'string', 'max:255'],
            // Description with a 1000-character plain-text limit (HTML stripped before counting)
            'description' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!is_string($value)) {
                        return;
                    }
                    // Strip HTML tags and collapse whitespace, then count characters
                    $text = preg_replace('/\s+/u', ' ', trim(strip_tags($value)));
                    $length = mb_strlen((string) $text);
                    if ($length > 1000) {
                        $fail(__('The :attribute may not be greater than :max characters.', [
                            'attribute' => __('description'),
                            'max' => 1000,
                        ]));
                    }
                },
            ],
            // Upload-on-save fields
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'facebook_url.max' => __('The Facebook URL may not be greater than 255 characters.'),
            'x_url.max' => __('The X URL may not be greater than 255 characters.'),
            'instagram_url.max' => __('The Instagram URL may not be greater than 255 characters.'),
            'youtube_url.max' => __('The YouTube URL may not be greater than 255 characters.'),
            // Custom rule uses this generic message, already translated above
            'profile_picture.image' => __('The file must be an image.'),
            'profile_picture.mimes' => __('The profile picture must be a file of type: jpeg, jpg, png, gif, webp.'),
            'profile_picture.max' => __('The profile picture may not be greater than 2MB.'),
            'profile_picture.dimensions' => __('The profile picture must be at least 100x100 pixels and no larger than 2000x2000 pixels.'),
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'facebook_url' => __('Facebook URL'),
            'x_url' => __('X URL'),
            'instagram_url' => __('Instagram URL'),
            'youtube_url' => __('YouTube URL'),
            'description' => __('description'),
            'profile_picture' => __('profile picture'),
            'remove_profile_picture' => __('remove profile picture'),
        ];
    }
}
