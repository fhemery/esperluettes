<?php

namespace App\Domains\Profile\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UploadProfilePictureRequest extends FormRequest
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
            'profile_picture' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:2048', // 2MB max
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'profile_picture.required' => __('Please select a profile picture to upload.'),
            'profile_picture.image' => __('The file must be an image.'),
            'profile_picture.mimes' => __('The profile picture must be a file of type: jpeg, jpg, png, gif, webp.'),
            'profile_picture.max' => __('The profile picture may not be greater than 2MB.'),
            'profile_picture.dimensions' => __('The profile picture must be at least 100x100 pixels and no larger than 2000x2000 pixels.'),
        ];
    }
}
