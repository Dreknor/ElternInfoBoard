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
            'read_receipt' => [
                'nullable', 'sometimes','boolean',
            ],
            'read_receipt_deadline' => [
                'nullable', 'date', 'after_or_equal:now',
            ],
            'external' => [
                'nullable', 'sometimes','boolean',
            ],
            'wp_push' => [
                'nullable', 'sometimes','boolean',
            ],
            'no_header' => [
                'nullable', 'sometimes','boolean',
            ],

        ];
    }
}
