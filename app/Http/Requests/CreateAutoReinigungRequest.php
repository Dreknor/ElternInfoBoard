<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAutoReinigungRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'aufgaben' => 'required|array',
            'aufgaben.*' => 'required|int|exists:reinigungs_tasks,id',
            'exclude' => 'nullable|array',
            'exclude.*' => 'required|int|exists:groups,id',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ];
    }
}
