<?php

namespace App\Http\Requests;

use App\Services\IntegrationTokens;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'lifetime' => ['required', Rule::in(array_keys(IntegrationTokens::LIFETIMES))],
            'scopes' => ['array'],
            'scopes.*' => [Rule::in(array_keys(IntegrationTokens::SCOPES))],
        ];
    }
}
