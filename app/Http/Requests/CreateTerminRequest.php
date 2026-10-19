<?php

namespace App\Http\Requests;

use App\Services\IndividualRecipients;
use Illuminate\Foundation\Http\FormRequest;

class CreateTerminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('edit termin') || $user->can('create termine'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'terminname' => [
                'required',
                'string',
            ],
            'start' => [
                'required',
                'date',
                'before:ende',
            ],
            'ende' => [
                'required',
                'date',
                'after:start',
            ],
            ...IndividualRecipients::rules(),
            'public' => [
                'nullable',
                'integer',
                'min:0',
                'max:1',
            ],

            'fullDay' => [
                'nullable',
                'integer',
                'min:0',
                'max:1',
            ],

        ];
    }

    public function messages(): array
    {
        return IndividualRecipients::messages();
    }
}
