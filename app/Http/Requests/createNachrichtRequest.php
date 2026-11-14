<?php

namespace App\Http\Requests;

use App\Services\IndividualRecipients;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class createNachrichtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (auth()->user()->can('create posts')) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Anhänge: nur erlaubte Dateitypen (config/uploads.php)
            'files' => ['nullable', 'array'],
            'files.*' => \App\Support\Uploads::fileRules(),
            'header' => [
                'required', 'max:120',
            ],
            'news' => [
                Rule::requiredIf(request()->type != 'image'),
            ],
            ...IndividualRecipients::rules(),
            'archiv_ab' => [
                'required', 'date',
            ],
            'type' => [
                'required',
            ],
            'reactable' => [
                'boolean',
            ],
            'released' => [
                'nullable', 'boolean',
            ],
            'read_receipt' => [
                'nullable', 'sometimes', 'boolean',
            ],
            'read_receipt_deadline' => [
                'nullable', 'date', 'after_or_equal:now',
            ],
            'read_receipt_scope' => [
                'nullable', 'in:family,person,child',
            ],
            'external' => [
                'nullable', 'sometimes', 'boolean',
            ],
            'wp_push' => [
                'nullable', 'sometimes', 'boolean',
            ],
            'no_header' => [
                'nullable', 'sometimes', 'boolean',
            ],
            // Vertraulich: nur Autor und Empfänger; nur mit „release posts“ (Autor gibt selbst frei)
            'confidential' => [
                'nullable', 'sometimes', 'boolean',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    if (! auth()->user()?->can('release posts')) {
                        $fail('Vertrauliche Beiträge können nur Nutzer anlegen, die Beiträge selbst freigeben dürfen.');
                    }
                    if (request()->boolean('external')) {
                        $fail('Externe Angebote können nicht vertraulich sein.');
                    }
                },
            ],

        ];
    }

    public function messages(): array
    {
        return IndividualRecipients::messages();
    }
}
