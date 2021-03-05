<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePushRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
'endpoint'    => [
                'required',
            ],
'keys.auth'   => [
                'required',
            ],
'keys.p256dh' => [
                'required',
            ],
];
    }
}
