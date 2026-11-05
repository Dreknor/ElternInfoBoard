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
            // Einzelne ID (ältere Apps, 0/leer = Sekretariat) oder Liste von IDs
            'mitarbeiter' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_array($value) && count(\App\Support\KontaktEmpfaenger::ids($value)) > \App\Support\KontaktEmpfaenger::MAX) {
                        $fail('Höchstens '.\App\Support\KontaktEmpfaenger::MAX.' Empfänger möglich.');
                    }
                },
            ],
            'mitarbeiter.*' => [
                'nullable', 'integer',
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
