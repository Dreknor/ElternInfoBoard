<?php

namespace App\Http\Requests\Arbeitsgemeinschaften;

use Illuminate\Foundation\Http\FormRequest;

class CreateGTARequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('edit GTA');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weekday' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'max_participants' => 'required|integer|min:1',
            'manager_id' => 'required|exists:users,id',
            'groups' => 'required|array|min:1',
            'groups.*' => 'exists:groups,id'

        ];
    }
}
