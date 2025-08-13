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
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'description.max' => __('The description may not be greater than 1000 characters.'),
            'facebook_url.max' => __('The Facebook URL may not be greater than 255 characters.'),
            'x_url.max' => __('The X URL may not be greater than 255 characters.'),
            'instagram_url.max' => __('The Instagram URL may not be greater than 255 characters.'),
            'youtube_url.max' => __('The YouTube URL may not be greater than 255 characters.'),
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
        ];
    }
}
