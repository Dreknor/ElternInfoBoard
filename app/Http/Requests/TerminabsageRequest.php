<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TerminabsageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $listen_termine = $this->route('listen_termine');
        if (auth()->user()->id == $listen_termine->reserviert_fuer or $listen_termine->reserviert_fuer == auth()->user()->sorg2 or auth()->user()->id == $listen_termine->liste->besitzer or auth()->user()->can('edit terminliste')) {
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
            'text' => ['nullable', 'string'],
        ];
    }
}
