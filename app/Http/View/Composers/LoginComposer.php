<?php

namespace App\Http\View\Composers;

class LoginComposer
{
    public function compose($view): void
    {
        // Use ENV variable only
        $keycloak = env('KEYCLOAK_ENABLED', false);
        $buttonText = env('KEYCLOAK_BUTTON_TEXT', 'Login mit SSO');

        $view->with('keycloak', $keycloak);
        $view->with('keycloakButtonText', $buttonText);
    }
}
