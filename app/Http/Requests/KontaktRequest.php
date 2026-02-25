<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class KontaktRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'text' => [
                'required',
                'string',
                'max:15000',
            ],
            'betreff' => [
                'required',
                'string',
                'max:255',
            ],
            'mitarbeiter' => [
                'present',
            ],
            'files' => [
                'nullable',
                'array',
                'max:3',
            ],
            'files.*' => [
                'file',
                'max:8000',
            ],
        ];
    }
}
