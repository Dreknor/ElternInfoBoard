<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('edit user');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'gruppen' => ['sometimes', 'array'],
            'roles' => ['sometimes', 'array'],
        ];
    }
}
