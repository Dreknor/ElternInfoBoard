<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class editPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $posts = $this->route('posts');

        if (auth()->user()->can('edit posts') or auth()->user()->id == $posts->author) {
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
                'required', 'max:120'
            ],
            'news' => [
                'news' => [
                    Rule::requiredIf(request()->type != 'image'),
                ],
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
                'nullable', 'boolean',
            ],
            'released' => [
                'nullable', 'boolean',
            ],
            'read_receipt' => [
                'nullable', 'boolean',
            ],
            'external' => [
                'nullable', 'sometimes','boolean',
            ],
            'wp_push' => [
                'nullable', 'sometimes','boolean',
            ],

        ];
    }
}
