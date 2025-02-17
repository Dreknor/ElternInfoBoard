<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChildNoticeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'notice' => [
                'nullable',
                'string',
                'max:500'
            ],
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
        ];
    }
}
