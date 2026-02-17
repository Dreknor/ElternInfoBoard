<?php

namespace App\Http\View\Composers;

use App\Settings\KeyCloakSetting;
use Illuminate\Support\Facades\Log;

class LoginComposer
{
    public function compose($view): void
    {
        try {
            $keycloakSettings = new KeyCloakSetting;
            $keycloak = $keycloakSettings->enabled ?? env('KEYCLOAK_ENABLED', false);

            Log::debug('LoginComposer - Keycloak status', [
                'enabled_from_settings' => $keycloakSettings->enabled ?? 'null',
                'enabled_from_env' => env('KEYCLOAK_ENABLED', 'not set'),
                'final_status' => $keycloak,
            ]);
        } catch (\Exception $e) {
            // Fallback to .env if settings are not available
            $keycloak = env('KEYCLOAK_ENABLED', false);

            Log::warning('LoginComposer - Failed to load KeyCloakSetting', [
                'error' => $e->getMessage(),
                'fallback_to_env' => $keycloak,
            ]);
        }

        $view->with('keycloak', $keycloak);
    }
}
