<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class editUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'unique:users,id,'.\auth()->user()->id,
            ],
            'email' => [
                'required',
                'email',
                'unique:users,id,'.\auth()->user()->id,
            ],
            'benachrichtigung' => [
                'required',
                'in:weekly,daily',
            ],
            'sendCopy' => [
                'nullable',
                'min:0',
                'max:1',
            ],
            'track_login' => [
                'nullable',
                'min:0',
                'max:1',
            ],
            'publicMail' => [
                'nullable',
                'email',
            ],
            'publicPhone' => [
                'nullable',
                'string',
            ],
            'calendar_prefix' => [
                'nullable',
                'string',
                'max:8',
            ],
            'releaseCalendar' => [
                'nullable',
                'integer',
                'min:0',
                'max:1',
            ],
            'changePassword' => [
                'nullable',
                'integer',
                'min:0',
                'max:1',
            ],
            'password' => [
                'nullable',
                'sometimes',
                'string',
                'min:8',
            ],

        ];
    }
}
