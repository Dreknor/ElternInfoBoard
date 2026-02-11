<?php

namespace App\Http\Requests;

use App\Settings\StundenplanSetting;
use Illuminate\Foundation\Http\FormRequest;

class ApiImportStundenplanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $settings = app(StundenplanSetting::class);

        // Check if API import is enabled
        if (!$settings->allow_api_import) {
            return false;
        }

        // Check API key from multiple sources (in order of preference):
        // 1. Query parameter: ?key=xxx
        // 2. Header: X-API-Key
        // 3. Bearer token
        // 4. JSON body (legacy support)

        $apiKey = null;

        // Try query parameter
        if ($this->query('key')) {
            $apiKey = $this->query('key');
        }
        // Try header
        elseif ($this->header('X-API-Key')) {
            $apiKey = $this->header('X-API-Key');
        }
        // Try Bearer token
        elseif ($this->bearerToken()) {
            $apiKey = $this->bearerToken();
        }
        // Try JSON body (legacy)
        else {
            $data = json_decode($this->getContent(), true);
            if (isset($data['key'])) {
                $apiKey = $data['key'];
            }
        }

        // Validate the API key
        if ($apiKey && $apiKey === $settings->import_api_key) {
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
        return [
            // Accept either direct format or Indiware Gesamtexport
            // The StundenplanDataAdapter will handle normalization
        ];
    }

    /**
     * Validate after normalization
     */
    protected function passedValidation()
    {
        // Additional validation will be done by StundenplanDataAdapter::validate()
    }
}



