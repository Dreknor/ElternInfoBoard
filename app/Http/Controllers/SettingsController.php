<?php

namespace App\Http\Controllers;

use App\Mail\TestEmail;
use App\Model\Group;
use App\Model\Groups;
use App\Model\Module;
use App\Model\User;
use App\Services\HolidayService;
use App\Settings\CareSetting;
use App\Settings\EmailSetting;
use App\Settings\GeneralSetting;
use App\Settings\NotifySetting;
use App\Settings\PflichtstundenSetting;
use App\Settings\ReminderSetting;
use App\Settings\SchickzeitenSetting;
use App\Settings\StundenplanSetting;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            ['permission:edit settings'],
        ];
    }

    public function index()
    {
        $settings = new GeneralSetting;
        $mailSettings = new EmailSetting;
        $notifySettings = new NotifySetting;
        $schickzeitenSetting = new SchickzeitenSetting;
        $careSettings = new CareSetting;
        $pflichtstundenSetting = new PflichtstundenSetting;
        $stundenplanSettings = new StundenplanSetting;
        $reminderSettings = new ReminderSetting;

        $groups = Group::all();
        $roles = Role::all();

        $users = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', '=', 'Mitarbeiter');
            })
            ->orderBy('name')
            ->get();

        return view('settings.index', [
            'settings' => $settings,
            'mailSettings' => $mailSettings,
            'notifySettings' => $notifySettings,
            'schickzeitenSettings' => $schickzeitenSetting,
            'careSettings' => $careSettings,
            'pflichtstundenSettings' => $pflichtstundenSetting,
            'stundenplanSettings' => $stundenplanSettings,
            'reminderSettings' => $reminderSettings,
            'groups' => Groups::query()->where('protected', 0)->get(),
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, $group): RedirectResponse
    {
        switch ($group) {
            case 'care':
                $validated = $request->validate([
                    'view_detailed_care' => 'nullable|boolean',
                    'hide_childs_when_absent' => 'nullable|boolean',
                    'groups_list' => 'nullable|array',
                    'class_list' => 'nullable|array',
                    'hide_groups_when_empty' => 'nullable|boolean',
                    'show_message_on_empty_group' => 'nullable|boolean',
                    'days_before_lock' => 'integer|min:1',
                    'info_to' => 'nullable|exists:users,id',
                    'end_time' => 'nullable|date_format:H:i',
                    'bundesland' => 'required|string|in:' . implode(',', array_keys(HolidayService::bundeslaender())),
                ]);

                $careSettings = new CareSetting;

                // Wenn Bundesland geändert wurde, Ferien-Cache invalidieren und alte Daten löschen
                $oldBundesland = $careSettings->bundesland;
                $newBundesland = $validated['bundesland'];
                if ($oldBundesland !== $newBundesland) {
                    // Alten Cache entfernen
                    $oldService = new HolidayService($oldBundesland);
                    $oldService->clearCache();

                    // Holidays des alten Bundeslandes löschen damit neue geladen werden
                    \App\Model\Holiday::where('bundesland', $oldBundesland)->delete();
                }

                $careSettings->view_detailed_care = $validated['view_detailed_care'] ?? false;
                $careSettings->hide_childs_when_absent = $validated['hide_childs_when_absent'] ?? false;
                $careSettings->groups_list = $validated['groups_list'] ?? [];
                $careSettings->class_list = $validated['class_list'] ?? [];
                $careSettings->hide_groups_when_empty = $validated['hide_groups_when_empty'] ?? false;
                $careSettings->show_message_on_empty_group = $validated['show_message_on_empty_group'] ?? false;
                $careSettings->days_before_lock = $validated['days_before_lock'] ?? 7;
                $careSettings->info_to = $validated['info_to'] ?? null;
                $careSettings->end_time = $validated['end_time'] ?? null;
                $careSettings->bundesland = $newBundesland;

                $careSettings->save();

                // Schickzeiten von Kindern löschen, die durch die neue Konfiguration
                // nicht mehr im Care-Modul sind
                $newGroups = $careSettings->groups_list;
                $newClasses = $careSettings->class_list;

                if (! empty($newGroups) && ! empty($newClasses)) {
                    $nonCareChildren = \App\Model\Child::query()
                        ->where(function ($query) use ($newGroups, $newClasses) {
                            $query->whereNotIn('group_id', $newGroups)
                                ->orWhereNotIn('class_id', $newClasses)
                                ->orWhereNull('group_id')
                                ->orWhereNull('class_id');
                        })
                        ->whereHas('schickzeiten')
                        ->get();

                    $deletedTotal = 0;
                    foreach ($nonCareChildren as $nonCareChild) {
                        $count = \App\Model\Schickzeiten::where('child_id', $nonCareChild->id)->count();
                        \App\Model\Schickzeiten::where('child_id', $nonCareChild->id)->each(function ($sz) {
                            $sz->delete();
                        });
                        $deletedTotal += $count;
                        \Illuminate\Support\Facades\Log::info(
                            "Schickzeiten für Kind {$nonCareChild->first_name} {$nonCareChild->last_name} (ID: {$nonCareChild->id}) gelöscht – Care-Einstellungen geändert.",
                            ['deleted_count' => $count]
                        );
                    }

                    if ($deletedTotal > 0) {
                        return redirect()->back()->with([
                            'Meldung' => "Einstellungen gespeichert. Es wurden {$deletedTotal} Schickzeit(en) von " . $nonCareChildren->count() . " Kind(ern) gelöscht, die nicht mehr im Care-Modul sind.",
                            'type' => 'warning',
                        ]);
                    }
                }

                break;
            case 'schickzeiten':
                $validated = $request->validate([
                    'schicken_ab' => 'required|date_format:H:i|before:schicken_bis',
                    'schicken_bis' => 'required|date_format:H:i|after:schicken_ab',
                    'schicken_text' => 'required|string',
                    'schicken_intervall' => 'required|numeric|min:1|max:60',
                ]);

                $schickzeitenSetting = new SchickzeitenSetting;
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

                $notifySettings = new NotifySetting;
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
                    'favicon' => 'sometimes|nullable|mimes:jpeg,png,jpg,gif,svg,ico|max:2048',
                ]);

                $settings = new GeneralSetting;
                $settings->app_name = $validated['app_name'];

                if ($request->hasFile('app_logo')) {
                    // Alte Logo-Datei löschen (außer Standard-Logo)
                    if ($settings->logo && $settings->logo !== 'app_logo.png' && Storage::disk('public')->exists('img/'.$settings->logo)) {
                        Storage::disk('public')->delete('img/'.$settings->logo);
                    }

                    $file = request()->file('app_logo');
                    $ext = $file->extension();
                    $name = Carbon::now()->format('YmdHis').'_logo'.'.'.$ext;

                    Storage::disk('public')->put('img/'.$name, file_get_contents($file));
                    $settings->logo = $name;

                }
                if ($request->hasFile('favicon')) {
                    // Alte Favicon-Datei löschen (außer Standard-Favicon)
                    if ($settings->favicon && $settings->favicon !== 'app_logo.png' && Storage::disk('public')->exists('img/'.$settings->favicon)) {
                        Storage::disk('public')->delete('img/'.$settings->favicon);
                    }

                    $file = request()->file('favicon');
                    $ext = $file->extension();
                    $name = Carbon::now()->format('YmdHis').'_favicon'.'.'.$ext;

                    Storage::disk('public')->put('img/'.$name, file_get_contents($file));
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
                    'new_user_welcome_text' => 'required|string|max:1000',
                    'log_sent_emails' => 'nullable|boolean',
                ]);

                $mailSettings = new EmailSetting;
                $mailSettings->mail_server = $validated['mail_server'];
                $mailSettings->mail_port = $validated['mail_port'];
                $mailSettings->mail_username = $validated['mail_username'];
                $mailSettings->mail_password = $validated['mail_password'];
                $mailSettings->mail_encryption = $validated['mail_encryption'];
                $mailSettings->mail_from_address = $validated['mail_from_address'];
                $mailSettings->mail_from_name = $validated['mail_from_name'];
                $mailSettings->new_user_welcome_text = $validated['new_user_welcome_text'];
                $mailSettings->log_sent_emails = $validated['log_sent_emails'] ?? false;
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
                    Mail::to(auth()->user())->send(new TestEmail);

                    return redirect()->back()->with([
                        'type' => 'success',
                        'Meldung' => 'Testmail wurde erfolgreich versendet',
                    ]);

                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'type' => 'danger',
                        'Meldung' => 'Fehler beim Versenden der Testmail. Bitte überprüfen Sie die Einstellungen. '.$e->getMessage(),
                    ]);
                }
                break;
            case 'pflichtstunden':
                $validated = $request->validate([
                    'pflichtstunden_start' => 'required|string',
                    'pflichtstunden_ende' => 'required|string',
                    'pflichtstunden_text' => 'required|string',
                    'pflichtstunden_anzahl' => 'required|integer|min:1',
                    'listen_autocreate' => 'nullable|boolean',
                    'pflichtstunden_betrag' => 'required|numeric|min:0',
                    'gamification_show_progress' => 'nullable|boolean',
                    'gamification_show_ranking' => 'nullable|boolean',
                    'gamification_show_comparison' => 'nullable|boolean',
                    'pflichtstunden_bereiche' => 'nullable|string',
                ]);

                try {
                    $start = Carbon::createFromFormat('m-d', $validated['pflichtstunden_start']);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'type' => 'danger',
                        'Meldung' => 'Falsches Datumsformat beim Startdatum',
                    ]);
                }

                try {
                    $end = Carbon::createFromFormat('m-d', $validated['pflichtstunden_ende']);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'type' => 'danger',
                        'Meldung' => 'Falsches Datumsformat beim Enddatum',
                    ]);
                }

                // Bereiche verarbeiten - HTML-Tags entfernen und bereinigen
                $bereiche = [];
                if (! empty($validated['pflichtstunden_bereiche'])) {
                    // HTML-Tags entfernen
                    $cleanText = strip_tags($validated['pflichtstunden_bereiche']);
                    // HTML-Entities dekodieren
                    $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    // In Zeilen aufteilen und bereinigen
                    $lines = explode("\n", $cleanText);
                    foreach ($lines as $line) {
                        // Whitespace entfernen und nur nicht-leere Zeilen hinzufügen
                        $trimmed = trim($line);
                        if (! empty($trimmed)) {
                            $bereiche[] = $trimmed;
                        }
                    }
                }

                $pflichtstundenSetting = new PflichtstundenSetting;
                $pflichtstundenSetting->pflichtstunden_start = $start->format('m-d');
                $pflichtstundenSetting->pflichtstunden_ende = $end->format('m-d');
                $pflichtstundenSetting->pflichtstunden_text = $validated['pflichtstunden_text'];
                $pflichtstundenSetting->pflichtstunden_anzahl = $validated['pflichtstunden_anzahl'];
                $pflichtstundenSetting->listen_autocreate = $request->has('listen_autocreate');
                $pflichtstundenSetting->pflichtstunden_betrag = $validated['pflichtstunden_betrag'];
                $pflichtstundenSetting->gamification_show_progress = $request->has('gamification_show_progress');
                $pflichtstundenSetting->gamification_show_ranking = $request->has('gamification_show_ranking');
                $pflichtstundenSetting->gamification_show_comparison = $request->has('gamification_show_comparison');
                $pflichtstundenSetting->pflichtstunden_bereiche = $bereiche;
                $pflichtstundenSetting->save();
                break;

            case 'stundenplan':
                $validated = $request->validate([
                    'allow_web_import' => 'nullable|boolean',
                    'allow_api_import' => 'nullable|boolean',
                    'show_absent_teachers' => 'nullable|boolean',
                ]);

                $stundenplanSetting = new StundenplanSetting;
                $stundenplanSetting->allow_web_import = $request->has('allow_web_import');
                $stundenplanSetting->allow_api_import = $request->has('allow_api_import');
                $stundenplanSetting->show_absent_teachers = $request->has('show_absent_teachers');
                $stundenplanSetting->save();

                // Clear cache
                Cache::forget('stundenplan_data');
                break;

            case 'reminder':
                $validated = $request->validate([
                    'send_time' => 'required|date_format:H:i',
                    'level1_active' => 'nullable|boolean',
                    'level1_days_before_deadline' => 'required|integer|min:1|max:30',
                    'level1_in_app' => 'nullable|boolean',
                    'level1_email' => 'nullable|boolean',
                    'level1_push' => 'nullable|boolean',
                    'level2_active' => 'nullable|boolean',
                    'level2_days_before_deadline' => 'required|integer|min:1|max:30',
                    'level2_in_app' => 'nullable|boolean',
                    'level2_email' => 'nullable|boolean',
                    'level2_push' => 'nullable|boolean',
                    'level3_active' => 'nullable|boolean',
                    'level3_days_after_deadline' => 'required|integer|min:0|max:30',
                    'level3_in_app' => 'nullable|boolean',
                    'level3_email' => 'nullable|boolean',
                    'level3_push' => 'nullable|boolean',
                    'level3_escalate_to_author' => 'nullable|boolean',
                    'include_rueckmeldungen' => 'nullable|boolean',
                    'include_read_receipts' => 'nullable|boolean',
                    'include_attendance_queries' => 'nullable|boolean',
                ]);

                $reminderSettings = new ReminderSetting;
                $reminderSettings->send_time = $validated['send_time'];
                $reminderSettings->level1_active = $request->has('level1_active');
                $reminderSettings->level1_days_before_deadline = $validated['level1_days_before_deadline'];
                $reminderSettings->level1_in_app = $request->has('level1_in_app');
                $reminderSettings->level1_email = $request->has('level1_email');
                $reminderSettings->level1_push = $request->has('level1_push');
                $reminderSettings->level2_active = $request->has('level2_active');
                $reminderSettings->level2_days_before_deadline = $validated['level2_days_before_deadline'];
                $reminderSettings->level2_in_app = $request->has('level2_in_app');
                $reminderSettings->level2_email = $request->has('level2_email');
                $reminderSettings->level2_push = $request->has('level2_push');
                $reminderSettings->level3_active = $request->has('level3_active');
                $reminderSettings->level3_days_after_deadline = $validated['level3_days_after_deadline'];
                $reminderSettings->level3_in_app = $request->has('level3_in_app');
                $reminderSettings->level3_email = $request->has('level3_email');
                $reminderSettings->level3_push = $request->has('level3_push');
                $reminderSettings->level3_escalate_to_author = $request->has('level3_escalate_to_author');
                $reminderSettings->include_rueckmeldungen = $request->has('include_rueckmeldungen');
                $reminderSettings->include_read_receipts = $request->has('include_read_receipts');
                $reminderSettings->include_attendance_queries = $request->has('include_attendance_queries');
                $reminderSettings->save();
                break;
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Einstellungen gespeichert',
        ]);
    }

    /**
     * Regenerate Stundenplan API Key
     */
    public function regenerateStundenplanApiKey(Request $request): RedirectResponse
    {
        $stundenplanSetting = new StundenplanSetting;
        $stundenplanSetting->import_api_key = \Illuminate\Support\Str::random(64);
        $stundenplanSetting->save();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Neuer API-Key erfolgreich generiert',
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
