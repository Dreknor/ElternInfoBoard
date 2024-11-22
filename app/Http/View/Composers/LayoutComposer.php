<?php

namespace App\Http\View\Composers;

use App\Model\Losung;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LayoutComposer
{
    public function compose($view, GeneralSetting $settings): void
    {

        $view->with('layout', $settings);
        $view->with('app', $settings);
    }
}
