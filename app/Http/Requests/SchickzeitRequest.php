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
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'child' => [
                'required',
                'string',
            ],
            'weekday'   => [
                'required',
                'in:Montag,Dienstag,Mittwoch,Donnerstag,Freitag',
            ],
            'time'      => [
                'required',
                'date_format:H:i',
            ],
            'time_spaet'=> [
                'sometimes',
                'nullable',
                'date_format:H:i',
            ],
            'type'      => [
                'sometimes',
                'string',
            ],
        ];
    }
}
