<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class createNachrichtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'header'    => [
                'required','max:120'
            ],
            'news' => [
                Rule::requiredIf(request()->type != 'image'),
            ],
            'gruppen' => [
                'required',
            ],
            'archiv_ab' => [
                'required', 'date',
            ],
            'password' => [
                'required_with:urgent',
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

        ];
    }
}
