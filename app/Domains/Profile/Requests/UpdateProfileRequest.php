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
            'description.max' => 'The description may not be greater than 1000 characters.',
            'facebook_url.max' => 'The Facebook URL may not be greater than 255 characters.',
            'x_url.max' => 'The X URL may not be greater than 255 characters.',
            'instagram_url.max' => 'The Instagram URL may not be greater than 255 characters.',
            'youtube_url.max' => 'The YouTube URL may not be greater than 255 characters.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'facebook_url' => 'Facebook URL',
            'x_url' => 'X URL',
            'instagram_url' => 'Instagram URL',
            'youtube_url' => 'YouTube URL',
            'description' => 'description',
        ];
    }
}
