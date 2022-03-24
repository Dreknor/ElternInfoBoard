<?php

namespace App\Http\Requests;

use App\Model\Post;
use Illuminate\Foundation\Http\FormRequest;

class editPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
    public function rules()
    {
        return [
            'header' => [
                'required',
            ],
            'news' => [
                'required',
            ],
            'gruppen' => [
                'required',
            ],
            'archiv_ab' => [
                'required', 'date'
            ],
            'password' => [
                'required_with:urgent',
            ],
            'type' => [
                'required',
            ],
            'reactable' => [
                'nullable', 'boolean'
            ],
            'released' => [
                'nullable', 'boolean'
            ],
        ];
    }
}
