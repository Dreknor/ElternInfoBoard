<?php

namespace App\Http\Controllers;

use App\Model\Module;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;
use Illuminate\Http\Request;


class SettingsController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(['permission:edit settings']);
    }


    public function index()
    {

        $settings = new GeneralSetting();
        return view('settings.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * @param Request $request
     * @param $group
     * @return RedirectResponse
     */
    public function update(Request $request, $group): RedirectResponse
    {
        switch ($group) {
            case 'general':
                $validated = $request->validate([
                    'app_name' => 'required|max:255',
                    'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'favicon' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]);

                $settings = new GeneralSetting();
                $settings->app_name = $validated['app_name'];

                if ($request->hasFile('app_logo')) {
                    $file = request()->file('app_logo');
                    $ext = $file->extension();
                    $name = Carbon::now()->format('YmdHis') . '_logo' . '.' . $ext;

                    Storage::disk('public')->put('img/' . $name, file_get_contents($file));
                    $settings->logo = $name;

                }
                if ($request->hasFile('favicon')) {
                    $file = request()->file('favicon');
                    $ext = $file->extension();
                    $name = Carbon::now()->format('YmdHis') . '_favicon' . '.' . $ext;

                    Storage::disk('public')->put('img/' . $name, file_get_contents($file));
                    $settings->favicon = $name;
                }
                $settings->save();
                break;
            default:
                return redirect()->back()->with([
                    'type' => 'danger',
                    'Meldung' => 'Fehler',
                ]);
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Einstellungen gespeichert',
        ]);
    }

    /**
     * @return View
     */
    public function module()
    {
        $module = Module::all();





        return view('settings.module', [
            'module' => $module,
        ]);
    }

    /**
     * @param string $modulname
     * @return RedirectResponse
     */
    public function change_status(string $modulname)
    {
        $modul = Module::where('setting', $modulname)->first();

        $options = $modul->options;
        if ($modul->options['active'] == 1) {
            $options['active'] = '0';

        } else {
            $options['active'] = '1';
        }
        $modul->options = $options;
        $modul->save();

        Cache::forget('modules');

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Status geändert',
        ]);
    }

    /**
     * @param string $modulname
     * @return RedirectResponse
     */
    public function change_nav(string $modulname)
    {
        $modul = Module::where('setting', $modulname)->first();
        $options = $modul->options;
        if (array_key_exists('nav', $modul->options)) {
            if (array_key_exists('bottom-nav', $options['nav']) and $options['nav']['bottom-nav'] == 'true') {
                $options['nav']['bottom-nav'] = 'false';
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
