<?php

namespace App\Http\View\Composers;

class LoginComposer
{
    public function compose($view): void
    {
        $keycloak = config('services.keycloak.enabled');
        $buttonText = config('services.keycloak.button_text');

        $view->with('keycloak', $keycloak);
        $view->with('keycloakButtonText', $buttonText);
    }
}
