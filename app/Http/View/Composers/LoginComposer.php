<?php

namespace App\Http\View\Composers;

use App\Model\Losung;
use App\Settings\GeneralSetting;
use App\Settings\KeyCloakSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LoginComposer
{
    public function compose($view): void
    {
        $keycloak = (new KeyCloakSetting())->enabled;
        $view->with('keycloak', $keycloak);
    }
}
