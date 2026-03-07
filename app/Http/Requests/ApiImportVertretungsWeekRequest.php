<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiKey;
use Illuminate\Foundation\Http\FormRequest;

class ApiImportVertretungsWeekRequest extends FormRequest
{
    use ValidatesApiKey;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->isValidApiKey();
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
