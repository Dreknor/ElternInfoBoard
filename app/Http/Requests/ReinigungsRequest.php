<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReinigungsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('edit reinigung');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'users_id' => [
                'required',
                'exists:users,id'
                ],
            'aufgabe' => ['required', 'exists:reinigungs_tasks,id'],
            'bemerkung' => ['nullable', 'string'],
            'datum' => ['required', 'date'],
        ];
    }
}
