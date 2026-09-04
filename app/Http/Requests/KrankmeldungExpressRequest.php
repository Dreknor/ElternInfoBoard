<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KrankmeldungExpressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create krankmeldung express');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'child_id' => [
                'required',
                'exists:children,id',
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
            'kommentar' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
