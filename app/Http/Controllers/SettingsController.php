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
}
