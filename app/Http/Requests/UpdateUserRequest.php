<?php

namespace App\Http\Requests;

use App\Model\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * FormRequest für Admin-Benutzerbearbeitung
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('edit user');
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
                Rule::unique(User::class)->ignore($this->user->id)->withoutTrashed(),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique(User::class)->ignore($this->user->id)->withoutTrashed(),
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
            'changePassword' => [
                'nullable',
                'min:0',
                'max:1',
            ],
            'new-password' => [
                'nullable',
                'string',
                Password::min(10)->mixedCase()->numbers(),
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}

