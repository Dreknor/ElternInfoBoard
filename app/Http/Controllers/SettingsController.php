<?php

namespace App\Http\Controllers;

use App\Mail\TestEmail;
use App\Model\Module;
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

        return view('settings.index', [
            'settings' => $settings,
            'mailSettings' => $mailSettings,
            'notifySettings' => $notifySettings,
            'KeyCloakSetting' => $KeyCloakSetting,
            'schickzeitenSettings' => $schickzeitenSetting,
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

            case 'schickzeiten':
                $validated = $request->validate([
                    'schicken_ab' => 'required|date_format:H:i|before:schicken_bis',
                    'schicken_bis' => 'required|date_format:H:i|after:schicken_ab',
                    'schicken_text' => 'required|string',
                    'schicken_intervall' => 'required|numeric|min:1|max:60',
                ]);

                $schickzeitenSetting = new SchickzeitenSetting();
                $schickzeitenSetting->schicken_ab = $validated['schicken_ab'];
                $schickzeitenSetting->schicken_bis = $validated['schicken_bis'];
                $schickzeitenSetting->schicken_text = $validated['schicken_text'];
                $schickzeitenSetting->schicken_intervall = $validated['schicken_intervall'];
                $schickzeitenSetting->save();



                break;

            case 'notifications':
                $validated = $request->validate([
                    'hour_send_information_mail' => 'required|numeric|max:23|min:0',
                    'weekday_send_information_mail' => 'required|numeric|max:6|min:1',
                    'hour_send_reminder_mail' => 'required|numeric|max:23|min:0',
                    'krankmeldungen_report_time' => 'required|date_format:H:i',
                    'schickzeiten_report_hour' => 'required|numeric',
                    'schickzeiten_report_weekday' => 'required|numeric',
                ]);

                $krankmeldungen_report_time = explode(':', $validated['krankmeldungen_report_time']);

                $notifySettings = new NotifySetting();
                $notifySettings->hour_send_information_mail = $validated['hour_send_information_mail'];
                $notifySettings->weekday_send_information_mail = $validated['weekday_send_information_mail'];
                $notifySettings->hour_send_reminder_mail = $validated['hour_send_reminder_mail'];
                $notifySettings->krankmeldungen_report_hour = $krankmeldungen_report_time[0];
                $notifySettings->krankmeldungen_report_minute = $krankmeldungen_report_time[1];
                $notifySettings->schickzeiten_report_hour = $validated['schickzeiten_report_hour'];
                $notifySettings->schickzeiten_report_weekday = $validated['schickzeiten_report_weekday'];
                $notifySettings->save();

                break;
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

            case 'email':

                $validated = $request->validate([
                    'mail_server' => 'required|max:255',
                    'mail_port' => 'required|max:255',
                    'mail_username' => 'required|max:255',
                    'mail_password' => 'required|max:255',
                    'mail_encryption' => 'required|max:255',
                    'mail_from_address' => 'required|max:255',
                    'mail_from_name' => 'required|max:255',
                ]);

                $mailSettings = new EmailSetting();
                $mailSettings->mail_server = $validated['mail_server'];
                $mailSettings->mail_port = $validated['mail_port'];
                $mailSettings->mail_username = $validated['mail_username'];
                $mailSettings->mail_password = $validated['mail_password'];
                $mailSettings->mail_encryption = $validated['mail_encryption'];
                $mailSettings->mail_from_address = $validated['mail_from_address'];
                $mailSettings->mail_from_name = $validated['mail_from_name'];
                $mailSettings->save();

                config([
                    'mail.mailers.smtp.host' => $mailSettings->mail_server,
                    'mail.mailers.smtp.port' => $mailSettings->mail_port,
                    'mail.mailers.smtp.username' => $mailSettings->mail_username,
                    'mail.mailers.smtp.password' => $mailSettings->mail_password,
                    'mail.mailers.smtp.encryption' => $mailSettings->mail_encryption,
                    'mail.from.address' => $mailSettings->mail_from_address,
                    'mail.from.name' => $mailSettings->mail_from_name,
                ]);

                Artisan::call('config:clear');
                Artisan::call('cache:clear');

                try {
                    Mail::to(auth()->user())->send(new TestEmail());

                    return redirect()->back()->with([
                        'type' => 'success',
                        'Meldung' => 'Testmail wurde erfolgreich versendet',
                    ]);

                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'type' => 'danger',
                        'Meldung' => 'Fehler beim Versenden der Testmail. Bitte überprüfen Sie die Einstellungen. ' . $e->getMessage(),
                    ]);
                }




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
