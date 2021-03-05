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
            'name' => [
                'required',
                'string',
            ],
            'kommentar' => [
                'required',
                'string',
            ],
            'start' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ende' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }
}
