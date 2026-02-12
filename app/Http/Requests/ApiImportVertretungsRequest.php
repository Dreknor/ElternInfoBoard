<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiImportVertretungsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
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
    public function rules(): array
    {

        $vertretung = json_decode(
            $this->getContent(),
            true
        );

        return [
            'date' => 'required|date',
            'klasse' => 'required_without:klasse_kurzform|string|exists:groups,name',
            'klasse_kurzform' => 'required_without:klasse|string|exists:stundenplan_klassen,kurzform',
            'stunde' => 'required|string',
            'altFach' => 'required|string',
            'neuFach' => 'string|nullable',
            'lehrer' => 'string|nullable',
            'comment' => 'string|nullable',
        ];
    }
}
