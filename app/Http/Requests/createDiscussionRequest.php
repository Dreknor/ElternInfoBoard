<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class createDiscussionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('view elternrat');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'header' => [
                'required',
                'string',
            ],
            'text' => [
                'required',
            ],
            'sticky' => [
                'required',
                'boolean',
            ],
        ];
    }
}
