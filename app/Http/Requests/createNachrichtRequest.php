<?php

namespace App\Http\Requests;

use App\Rules\ValidCurrentUserPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class createNachrichtRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
    public function rules()
    {
        return [
            'header'    => [
                'required',
            ],
            'news'      => [
                'required',
            ],
            'gruppen' => [
                'required',
            ],
            'password' => [
                'required_with:urgent',
            ],
            'type' => [
                'required',
            ],
            'reactable' => [
                'boolean'
            ],
        ];
    }
}
