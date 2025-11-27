<?php

namespace App\Domains\Auth\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParentalAuthorizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'parental_authorization' => [
                'required',
                'file',
                'mimes:pdf',
                'min:1', // Prevent empty files
                'max:5120', // 5MB max
            ],
        ];
    }

    /**
     * Get the custom error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'parental_authorization.required' => __('auth::compliance.parental_authorization.required'),
            'parental_authorization.file' => __('auth::compliance.parental_authorization.file'),
            'parental_authorization.mimes' => __('auth::compliance.parental_authorization.mimes'),
            'parental_authorization.min' => __('auth::compliance.parental_authorization.min'),
            'parental_authorization.max' => __('auth::compliance.parental_authorization.max'),
            'parental_authorization.uploaded' => __('auth::compliance.parental_authorization.uploaded'),
        ];
    }

    /**
     * Get the custom attributes for the defined validation rules.
     */
    public function attributes(): array
    {
        return [
            'parental_authorization' => __('auth::compliance.parental_authorization.attribute'),
        ];
    }
}
