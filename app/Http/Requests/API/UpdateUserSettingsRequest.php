<?php

namespace App\Http\Requests\API;

use App\Services\UserAppSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'path' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!UserAppSettingsService::isValidPath($value)) {
                        $fail('Invalid path: ' . $value);
                    }
                },
            ],
            'value' => 'required',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'path.required' => 'Path is required',
            'value.required' => 'Value is required',
        ];
    }
}

