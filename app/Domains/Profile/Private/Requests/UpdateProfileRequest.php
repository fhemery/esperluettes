<?php

namespace App\Domains\Profile\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Domains\Shared\Validation\Rules\UniqueProfileDisplayName;

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
            'display_name' => ['required', 'string', 'min:2', 'max:100', new UniqueProfileDisplayName(Auth::id())],
            'facebook_handle'  => ['nullable', 'string', 'max:255'],
            'x_handle'         => ['nullable', 'string', 'max:255'],
            'instagram_handle' => ['nullable', 'string', 'max:255'],
            'youtube_handle'   => ['nullable', 'string', 'max:255'],
            'tiktok_handle'    => ['nullable', 'string', 'max:255'],
            'bluesky_handle'   => ['nullable', 'string', 'max:255'],
            'mastodon_handle'  => ['nullable', 'string', 'max:255'],
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
                        $fail(__('profile::validation.description_max', [
                            'attribute' => __('profile::fields.description'),
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
            'display_name.required' => __('profile::validation.display_name_required'),
            'display_name.min' => __('profile::validation.display_name_min'),
            'display_name.max' => __('profile::validation.display_name_max'),
            'facebook_handle.max'  => __('profile::validation.facebook_handle_max'),
            'x_handle.max'         => __('profile::validation.x_handle_max'),
            'instagram_handle.max' => __('profile::validation.instagram_handle_max'),
            'youtube_handle.max'   => __('profile::validation.youtube_handle_max'),
            'tiktok_handle.max'    => __('profile::validation.tiktok_handle_max'),
            'bluesky_handle.max'   => __('profile::validation.bluesky_handle_max'),
            'mastodon_handle.max'  => __('profile::validation.mastodon_handle_max'),
            'profile_picture.image' => __('profile::validation.profile_picture_image'),
            'profile_picture.mimes' => __('profile::validation.profile_picture_mimes'),
            'profile_picture.max' => __('profile::validation.profile_picture_max'),
            'profile_picture.dimensions' => __('profile::validation.profile_picture_dimensions'),
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'display_name' => __('profile::fields.display name'),
            'facebook_handle'  => __('profile::fields.facebook_handle'),
            'x_handle'         => __('profile::fields.x_handle'),
            'instagram_handle' => __('profile::fields.instagram_handle'),
            'youtube_handle'   => __('profile::fields.youtube_handle'),
            'tiktok_handle'    => __('profile::fields.tiktok_handle'),
            'bluesky_handle'   => __('profile::fields.bluesky_handle'),
            'mastodon_handle'  => __('profile::fields.mastodon_handle'),
            'description' => __('profile::fields.description'),
            'profile_picture' => __('profile::fields.profile picture'),
            'remove_profile_picture' => __('profile::fields.remove profile picture'),
        ];
    }
}
