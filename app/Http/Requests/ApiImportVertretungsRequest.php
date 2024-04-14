<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ApiImportVertretungsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $vertretung = json_decode(
            $this->getContent(),
            true
        );

        if ($vertretung['key'] == config('app.api_key')) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {

        $vertretung = json_decode(
            $this->getContent(),
            true
        );

        return [
            'date' => 'required|date',
            'klasse' => 'required|string|exists:groups,name',
            'stunde' => 'required|string',
            'altFach' => 'required|string',
            'neuFach' => 'string|nullable',
            'lehrer' => 'string|nullable',
            'comment' => 'string|nullable',
        ];
    }
}
