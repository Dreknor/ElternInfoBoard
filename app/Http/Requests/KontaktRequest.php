<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KontaktRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'text' => [
                'required',
                'string',
            ],
            'betreff' => [
                'required',
                'string',
            ],
            'mitarbeiter' => [
                'present',
            ],
        ];
    }
}
