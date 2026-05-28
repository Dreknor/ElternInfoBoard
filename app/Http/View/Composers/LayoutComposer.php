<?php

namespace App\Http\View\Composers;

use App\Settings\GeneralSetting;

class LayoutComposer
{
    public function compose($view, GeneralSetting $settings): void
    {
        $view->with('layout', config('services.keycloak.enabled'));
    }
}
