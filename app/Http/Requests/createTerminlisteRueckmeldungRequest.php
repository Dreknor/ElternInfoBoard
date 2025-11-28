<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class createTerminlisteRueckmeldungRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('create posts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empfaenger' => ['nullable', 'email:rfc,dns,strict'],
            'ende' => ['required', 'date'],
            'pflicht' => ['nullable', Rule::in([0, 1])],
            'liste_id' => ['required', 'exists:listen,id'],
            'terminliste_start_date' => ['required', 'date', 'after_or_equal:today'],
            'terminliste_end_date' => ['required', 'date', 'after_or_equal:terminliste_start_date'],
        ];
    }
}

