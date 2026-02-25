<?php

namespace App\Http\View\Composers;

use App\Settings\GeneralSetting;

class LayoutComposer
{
    public function compose($view, GeneralSetting $settings): void
    {
        // KeyCloak wird jetzt nur über ENV gesteuert
        $view->with('layout', env('KEYCLOAK_ENABLED', false));
    }
}
