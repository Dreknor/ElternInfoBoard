<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class createAbfrageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('create posts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'description' => ['required', 'string'],
            'ende' => ['required', 'date', 'after:today'],
            'max_number' => ['required', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:2'],
            'types' => ['required', 'array', 'min:2'],
            'empfaenger' => ['required', 'exists:users,email'],
            'pflicht' => ['integer', 'nullable', 'max:1'],
            'multiple' => ['integer', 'nullable', 'max:1'],

        ];
    }
}
