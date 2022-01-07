<?php

namespace App\Http\Controllers;

use App\Model\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(['permission:edit settings']);
    }

    public function module()
    {
        $module = Settings::where('category', 'module')->get();

        return view('settings.module', [
            'module' => $module,
        ]);
    }

    public function change_status($modulname)
    {
        $modul = Settings::where('setting', $modulname)->first();

        if ($modul->options['active'] == 1) {
            $options = $modul->options;
            $options['active'] = '0';
            $modul->options = $options;

            $modul->save();
        } else {
            $options = $modul->options;
            $options['active'] = '1';
            $modul->options = $options;

            $modul->save();
        }

        Cache::forget('modules');

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Status geändert',
        ]);
    }

    public function change_nav($modulname)
    {
        $modul = Settings::where('setting', $modulname)->first();
        $options = $modul->options;
        if (array_key_exists('nav', $modul->options)) {


            if (array_key_exists('bottom-nav', $options['nav']) and $options['nav']['bottom-nav'] == "true") {
                $options['nav']['bottom-nav'] = "false";
            } else {

                $options['nav']['bottom-nav'] = 'true';
            }
            $modul->options = $options;
            $modul->save();
            Cache::forget('modules');

            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => 'Status geändert',
            ]);

        }


        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Fehlgeschlagen',
        ]);
    }
}
