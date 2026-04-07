<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesApiKey;
use App\Settings\StundenplanSetting;
use Illuminate\Foundation\Http\FormRequest;

class ApiImportStundenplanRequest extends FormRequest
{
    use ValidatesApiKey;

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

        // Konfigurierter Key aus den Settings überschreibt app.api_key
        $configuredKey = $settings->import_api_key;
        if (!empty($configuredKey)) {
            $apiKey = $this->header('X-API-Key') ?? $this->bearerToken();
            // Legacy: Key im JSON-Body (rückwärtskompatibel)
            if (empty($apiKey)) {
                $body = json_decode($this->getContent(), true);
                $apiKey = $body['key'] ?? null;
            }
            if (empty($apiKey)) {
                return false;
            }
            return hash_equals($configuredKey, $apiKey);
        }

        return $this->isValidApiKey();
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

