<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTerminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->can('edit termin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'terminname' => [
                'required',
                'string',
            ],
            'start' => [
                'required',
                'date',
                'before:ende',
            ],
            'ende' => [
                'required',
                'date',
                'after:start',
            ],
            'gruppen' => [
                'required',
            ],
            'public' => [
                'nullable',
                'integer',
                'min:0',
                'max:1',
            ],

            'fullDay' => [
                'nullable',
                'integer',
                'min:0',
                'max:1',
            ],

        ];
    }
}
