<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KrankmeldungRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * „keine genannte“ wird im Formular als 0 gesendet.
     */
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('disease_id'), [0, '0', ''], true)) {
            $this->merge(['disease_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Anhänge: nur erlaubte Dateitypen (config/uploads.php)
            'files' => ['nullable', 'array'],
            'files.*' => \App\Support\Uploads::fileRules(),
            'name' => [
                'sometimes',
                'nullable',
                'string',
                'max:400',
            ],
            'child_id' => [
                'nullable',
                'exists:children,id',
            ],
            'kommentar' => [
                'required',
                'string',
                'max:10000',
            ],
            'start' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ende' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start',
            ],
            // Feldname wie im Formular und in der Tabelle (vorher „disease“ – die Krankheit
            // landete dadurch nie in der Krankmeldung).
            'disease_id' => [
                'nullable',
                'exists:diseases,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ende.after_or_equal' => 'Das Ende darf nicht vor dem Beginn liegen.',
        ];
    }
}
