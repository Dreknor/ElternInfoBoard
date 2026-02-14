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
        $isAuthorized = auth()->check();
        Log::channel('single')->info('KontaktRequest: authorize() called', [
            'is_authorized' => $isAuthorized,
            'user_id' => auth()->id(),
        ]);
        return $isAuthorized;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        Log::channel('single')->info('KontaktRequest: rules() called', [
            'has_text' => $this->has('text'),
            'has_betreff' => $this->has('betreff'),
            'has_mitarbeiter' => $this->has('mitarbeiter'),
            'has_files' => $this->hasFile('files'),
        ]);

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
