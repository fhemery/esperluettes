<?php

namespace App\Domains\Discord\Private\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DiscordConnectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // API key auth handled by middleware; allow validation to proceed
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->input('code')) ? trim((string) $this->input('code')) : $this->input('code'),
            'discordId' => is_string($this->input('discordId')) ? trim((string) $this->input('discordId')) : $this->input('discordId'),
            'discordUsername' => is_string($this->input('discordUsername')) ? trim((string) $this->input('discordUsername')) : $this->input('discordUsername'),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['bail', 'required', 'string', 'filled'],
            'discordId' => ['bail', 'required', 'string', 'filled', 'regex:/^\d{17,19}$/'],
            'discordUsername' => ['bail', 'required', 'string', 'filled'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'error' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 400));
    }
}
