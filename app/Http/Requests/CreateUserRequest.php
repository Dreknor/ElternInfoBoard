<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('edit user');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            // Soft-gelöschte Benutzer mit derselben E-Mail blockieren die Anlage nicht,
            // da sie stattdessen wiederhergestellt werden können (siehe UserController::store).
            'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'gruppen' => ['sometimes', 'array'],
            'roles' => ['sometimes', 'array'],
        ];
    }
}
