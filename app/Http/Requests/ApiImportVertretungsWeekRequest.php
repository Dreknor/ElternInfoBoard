<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiImportVertretungsWeekRequest extends FormRequest
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
            'week' => 'required|date',
            'type' => 'required|string',
            'id' => 'required|integer',
        ];
    }
}
