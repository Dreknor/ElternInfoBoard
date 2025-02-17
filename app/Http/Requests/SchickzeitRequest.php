<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchickzeitRequest extends FormRequest
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
            'time' => [
                'sometimes',
                'date_format:H:i',
                'nullable',
            ],
            'type' => [
                'required',
                'string',
                'in:genau,ab,spät.',
            ],
            'weekday' => [
                'nullable',
                'string',
                'in:Montag,Dienstag,Mittwoch,Donnerstag,Freitag',
            ],
            'specific_date' => [
                'nullable',
                'date',
                'after_or_equal:' . now()->toDateString(),
            ],
            'time_ab' => [
                'nullable',
                'date_format:H:i',
            ],
            'time_spaet' => [
                'nullable',
                'date_format:H:i',
            ],
        ];
    }
}
