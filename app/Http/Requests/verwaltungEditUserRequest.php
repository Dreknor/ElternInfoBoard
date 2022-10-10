<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class verwaltungEditUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \auth()->user()->can('edit user');
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
                'unique:users,id,'.$this->user->id,
            ],
            'email' => [
                'required',
                'email',
                'unique:users,id,'.$this->user->id,
            ],
            'publicMail' => [
                'nullable',
                'email',
            ],
            'publicPhone' => [
                'nullable',
                'string',
            ],
            'benachrichtigung' => [
                'required',
                'in:weekly,daily',
            ],
            'sorg2' => [
                'nullable',
                'exists:users,id',
            ],
        ];
    }
}
