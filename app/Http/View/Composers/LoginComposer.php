<?php

namespace App\Http\View\Composers;

use App\Settings\KeyCloakSetting;

class LoginComposer
{
    public function compose($view): void
    {
        $keycloak = (new KeyCloakSetting)->enabled;
        $view->with('keycloak', $keycloak);
    }
}
