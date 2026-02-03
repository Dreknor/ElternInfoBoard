<?php

namespace App\Http\View\Composers;

use App\Settings\GeneralSetting;
use App\Settings\KeyCloakSetting;

class LayoutComposer
{
    public function compose($view, GeneralSetting $settings): void
    {

        $view->with('layout', (new KeyCloakSetting)->enabled);
    }
}
