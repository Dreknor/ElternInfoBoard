<?php

namespace App\Http\View\Composers;

class LoginComposer
{
    public function compose($view): void
    {
        $keycloak   = config('services.keycloak.enabled');
        $buttonText = config('services.keycloak.button_text');

        // UCS-Login: sichtbar wenn KeyclockSetting::enabled (services.keycloak.enabled)
        // UND UcsSetting::enabled. Da UcsServiceProvider services.keycloak.enabled setzt,
        // genügt hier eine separate UCS-Prüfung aus der DB.
        $ucsEnabled = false;
        try {
            if (app()->bound(\App\Settings\UcsSetting::class)) {
                $ucsEnabled = (bool) app(\App\Settings\UcsSetting::class)->enabled;
            }
        } catch (\Throwable) {
            $ucsEnabled = false;
        }

        $view->with('keycloak', $keycloak);
        $view->with('keycloakButtonText', $buttonText);
        $view->with('ucsEnabled', $ucsEnabled && $keycloak);
    }
}
