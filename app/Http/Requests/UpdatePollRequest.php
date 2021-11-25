<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('create polls');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'poll_name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'ends' => ['required', 'date', 'after:today'],
            'max_number' => ['required', 'integer', 'min:1'],
            'options' => ['required']
        ];
    }
}
