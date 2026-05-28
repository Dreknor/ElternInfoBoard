<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiKey;
use Illuminate\Foundation\Http\FormRequest;

class ApiUpdateAbsenceRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'name' => 'required|string',
            'reason' => 'nullable|string',
        ];
    }
}

