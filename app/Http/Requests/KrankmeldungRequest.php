<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KrankmeldungRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'child_id' => [
                'nullable',
                'exists:children,id',
            ],
            'kommentar' => [
                'required',
                'string',
                'max:10000'
            ],
            'start' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ende' => [
                'required',
                'date_format:Y-m-d',
            ],
            'disease' => [
                'nullable',
                'exists:diseases,id',
            ],
        ];
    }
}
