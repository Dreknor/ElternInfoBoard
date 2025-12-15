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
            'end' => [
                'required',
                'date',
                'after:start',
                'before:tomorrow',
                function ($attribute, $value, $fail) {
                    $start = $this->input('start');
                    if ($start) {
                        $startDate = new \DateTime($start);
                        $endDate = new \DateTime($value);
                        $diff = $startDate->diff($endDate);
                        $hours = ($diff->days * 24) + $diff->h + ($diff->i / 60);

                        if ($hours > 24) {
                            $fail('Start- und Endzeit dürfen nicht mehr als 24 Stunden auseinander liegen.');
                        }
                    }
                },
            ],
            'description' => ['required', 'string', 'max:500'],
            'user_id' => ['sometimes', 'exists:users,id'] ,
            'bereich' => ['nullable', 'string', 'max:255'],
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
