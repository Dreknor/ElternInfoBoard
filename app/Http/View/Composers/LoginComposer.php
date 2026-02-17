<?php

namespace App\Http\View\Composers;

use App\Settings\KeyCloakSetting;

class LoginComposer
{
    public function compose($view): void
    {
        try {
            $keycloakSettings = new KeyCloakSetting;
            $keycloak = $keycloakSettings->enabled ?? env('KEYCLOAK_ENABLED', false);
        } catch (\Exception $e) {
            // Fallback to .env if settings are not available
            $keycloak = env('KEYCLOAK_ENABLED', false);
        }

        $view->with('keycloak', $keycloak);
    }
}
