<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class createRueckmeldungRequest extends FormRequest
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
            'ende'  => [
                'required',
                'date',
            ],
            'empfaenger'    => [
                'required',
                'email',
            ],
            'text'  => [
                'required',
            ],
        ];
    }
}
