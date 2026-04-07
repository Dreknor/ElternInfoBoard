<?php

namespace App\Http\Requests\Concerns;

/**
 * Trait für die einheitliche, timing-sichere API-Key-Validierung.
 *
 * Der Key wird bevorzugt aus dem Header 'X-API-Key' oder als Bearer-Token gelesen.
 * Zur Rückwärtskompatibilität wird der Key auch noch aus dem JSON-Body akzeptiert,
 * dies wird aber als veraltet betrachtet und sollte migriert werden.
 */
trait ValidatesApiKey
{
    protected function isValidApiKey(): bool
    {
        $configKey = config('app.api_key');

        // Kein API-Key konfiguriert → Zugriff verweigern
        if (empty($configKey)) {
            return false;
        }

        // 1. Bevorzugt: X-API-Key Header
        $apiKey = $this->header('X-API-Key');

        // 2. Fallback: Bearer Token
        if (empty($apiKey)) {
            $apiKey = $this->bearerToken();
        }

        // 3. Legacy-Fallback: Key im JSON-Body (rückwärtskompatibel, aber veraltet)
        if (empty($apiKey)) {
            $body = json_decode($this->getContent(), true);
            $apiKey = $body['key'] ?? null;
        }

        if (empty($apiKey)) {
            return false;
        }

        // Timing-sicherer Vergleich verhindert Timing-Attacks
        return hash_equals($configKey, $apiKey);
    }
}

