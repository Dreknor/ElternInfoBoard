<?php

namespace App\Http\View\Composers;

class LoginComposer
{
    public function compose($view): void
    {
        // Use ENV variable only
        $keycloak = env('KEYCLOAK_ENABLED', false);

        $view->with('keycloak', $keycloak);
    }
}
