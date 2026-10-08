<?php

namespace App\Console\Commands;

use App\Model\User;
use App\Model\UserDevice;
use App\Services\Push\FcmSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Prüft die Einrichtung der Push-Mitteilungen für die Eltern-App und sendet optional
 * eine Test-Mitteilung an die Geräte eines Nutzers (direkt, ohne Queue).
 */
class PushCheckCommand extends Command
{
    protected $signature = 'push:check {email? : E-Mail eines Nutzers, der eine Test-Mitteilung erhalten soll}';

    protected $description = 'Einrichtung der App-Mitteilungen (FCM/APNs) prüfen und Test-Mitteilung senden';

    private bool $ok = true;

    public function handle(FcmSender $fcm): int
    {
        $this->info('=== Server ===');
        $this->check('App-API v1 vorhanden (Route api.v1.devices.store)', Route::has('api.v1.devices.store'),
            'Branch feat/app-api-v1 einspielen; ggf. `php artisan route:clear`.');
        $this->check('Tabelle user_devices vorhanden', Schema::hasTable('user_devices'),
            '`php artisan migrate --force` ausführen.');

        $this->info('=== Firebase (Android) ===');
        $path = config('services.fcm.credentials');
        $this->check('FCM_CREDENTIALS gesetzt ('.($path ?: 'leer').')', ! empty($path),
            'In .env FCM_CREDENTIALS=storage/app/private/firebase.json setzen, dann `php artisan config:cache`.');
        if ($path) {
            $this->check('Datei lesbar: '.base_path($path), is_readable(base_path($path)),
                'Datei hochladen bzw. Pfad/Dateirechte prüfen (Pfad relativ zum Laravel-Ordner).');
        }
        if ($fcm->isConfigured()) {
            $c = $fcm->credentials();
            $valid = isset($c['project_id'], $c['client_email'], $c['private_key']);
            $this->check('Dienstkonto-Schlüssel gültig'.($valid ? " (Projekt: {$c['project_id']})" : ''), $valid,
                'Falsche Datei? Benötigt wird der Schlüssel aus Projekteinstellungen → Dienstkonten, nicht google-services.json.');
            if ($valid) {
                try {
                    Cache::forget('fcm_access_token');
                    $fcm->accessToken();
                    $this->check('Anmeldung bei Google erfolgreich', true);
                } catch (\Throwable $e) {
                    $this->check('Anmeldung bei Google erfolgreich', false, mb_strimwidth($e->getMessage(), 0, 300, '…'));
                }
            }
        }

        $this->info('=== Warteschlange ===');
        $connection = config('queue.default');
        $this->line("Queue-Verbindung: {$connection}");
        if ($connection !== 'sync') {
            $this->check('USE_CRONJOB aktiv (Queue wird per `schedule:run` abgearbeitet)', (bool) config('queue.use_cronjob'),
                'Ohne dauerhaft laufenden Queue-Worker (Webhosting) USE_CRONJOB=true setzen und Cronjob `php artisan schedule:run` jede Minute einrichten.');
        }
        if ($connection === 'database' && Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')->count();
            $oldest = DB::table('jobs')->min('created_at');
            $this->line("Wartende Jobs: {$pending}".($oldest ? ' (ältester: '.date('d.m.Y H:i', $oldest).')' : ''));
            if ($oldest && $oldest < now()->subMinutes(10)->timestamp) {
                $this->check('Warteschlange wird abgearbeitet', false, 'Jobs warten länger als 10 Minuten – läuft der Cronjob?');
            }
        }
        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->where('payload', 'like', '%SendNativePush%')->count();
            $this->line("Fehlgeschlagene Push-Jobs: {$failed}");
        }

        if (Schema::hasTable('user_devices')) {
            $this->info('=== Geräte ===');
            $this->line('Registrierte Geräte: '.UserDevice::count()
                .' (Android: '.UserDevice::where('provider', UserDevice::PROVIDER_FCM)->count()
                .', iOS: '.UserDevice::where('provider', UserDevice::PROVIDER_APNS)->count().')');
        }

        if ($email = $this->argument('email')) {
            $this->sendTest($fcm, $email);
        }

        $this->newLine();
        $this->ok ? $this->info('Alles in Ordnung.') : $this->error('Es gibt Probleme (siehe oben).');

        return $this->ok ? self::SUCCESS : self::FAILURE;
    }

    private function sendTest(FcmSender $fcm, string $email): void
    {
        $this->info("=== Test-Mitteilung an {$email} ===");
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->check('Nutzer gefunden', false);

            return;
        }
        $devices = UserDevice::where('user_id', $user->id)->get();
        $this->check('Gerät registriert', $devices->isNotEmpty(),
            'In der App unter Mehr → Einstellungen „Mitteilungen erlauben“ tippen.');

        foreach ($devices as $device) {
            $label = ($device->device_name ?: $device->platform)." ({$device->provider})";
            if ($device->provider !== UserDevice::PROVIDER_FCM) {
                $this->line("– {$label}: übersprungen (nur Android wird hier getestet)");

                continue;
            }
            if (! $fcm->isConfigured()) {
                $this->check("{$label}: gesendet", false, 'Firebase ist nicht eingerichtet.');

                continue;
            }
            $result = $fcm->send($device->token, 'Test', 'Mitteilungen funktionieren.', ['type' => 'info']);
            $this->check("{$label}: gesendet", $result === true, match ($result) {
                false => 'Token ungültig oder gehört zu einem anderen Firebase-Projekt (google-services.json der App und Dienstkonto müssen zum selben Projekt gehören).',
                default => 'Versand fehlgeschlagen – Details in storage/logs.',
            });
        }
    }

    private function check(string $label, bool $passed, string $hint = ''): void
    {
        if ($passed) {
            $this->line("<info>✓</info> {$label}");

            return;
        }
        $this->ok = false;
        $this->line("<error>✗</error> {$label}");
        if ($hint) {
            $this->line("  → {$hint}");
        }
    }
}
