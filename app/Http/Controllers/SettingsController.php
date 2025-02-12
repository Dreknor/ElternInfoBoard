<?php

namespace App\Http\Controllers;

use App\Mail\TestEmail;
use App\Model\Groups;
use App\Model\Module;
use App\Settings\CareSetting;
use App\Settings\EmailSetting;
use App\Settings\GeneralSetting;
use App\Settings\KeyCloakSetting;
use App\Settings\NotifySetting;
use App\Settings\SchickzeitenSetting;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
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
        $mailSettings = new EmailSetting();
        $notifySettings = new NotifySetting();
        $KeyCloakSetting = new KeyCloakSetting();
        $schickzeitenSetting = new SchickzeitenSetting();
        $careSettings = new CareSetting();

        return view('settings.index', [
            'settings' => $settings,
            'mailSettings' => $mailSettings,
            'notifySettings' => $notifySettings,
            'KeyCloakSetting' => $KeyCloakSetting,
            'schickzeitenSettings' => $schickzeitenSetting,
            'careSettings' => $careSettings,
            'groups' => Groups::query()->where('protected', 0)->get(),
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

            case 'care':
                $validated = $request->validate([
                    'view_detailed_care' => 'nullable|boolean',
                    'hide_childs_when_absent' => 'nullable|boolean',
                    'groups_list' => 'nullable|array',
                    'class_list' => 'nullable|array',
                ]);


                $careSettings = new CareSetting();
                $careSettings->view_detailed_care = $validated['view_detailed_care'] ?? false;
                $careSettings->hide_childs_when_absent = $validated['hide_childs_when_absent'] ?? false;
                $careSettings->groups_list = $validated['groups_list'] ?? [];
                $careSettings->class_list = $validated['class_list'] ?? [];
                $careSettings->save();

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
