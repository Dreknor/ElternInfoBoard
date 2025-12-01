<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePflichtstundeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('view Pflichtstunden');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start' => ['required', 'date', 'before:end', 'before:tomorrow'],
            'end' => ['required', 'date', 'after:start', 'before:tomorrow'],
            'description' => ['required', 'string', 'max:500'],
            'user_id' => ['sometimes', 'exists:users,id'] // Für Verwaltung
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Bitte geben Sie einen Grund für die Pflichtstunden an.',
            'description.max' => 'Der Grund darf maximal 500 Zeichen lang sein.',
            'start.before' => 'Das Startdatum muss vor dem Enddatum liegen.',
            'end.after' => 'Das Enddatum muss nach dem Startdatum liegen.',
        ];
    }
}
