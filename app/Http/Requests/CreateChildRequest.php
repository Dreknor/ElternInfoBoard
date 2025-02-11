<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateChildRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'group_id' => ['sometimes', 'integer', 'exists:groups,id'],
            'class_id' => ['sometimes', 'integer', 'exists:groups,id'],
        ];
    }
}
