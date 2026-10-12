<?php

namespace App\Http\Controllers;

use App\Services\Updater\UpdateService;
use Closure;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/**
 * Oberfläche des Online-Updaters. Die Ausführung selbst übernimmt der
 * Scheduler (updater:run --if-requested), damit das Update nicht an die
 * Laufzeit- und Rechtegrenzen des Webservers gebunden ist.
 */
class UpdaterController extends Controller implements HasMiddleware
{
    public function __construct(private readonly UpdateService $updater) {}

    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, Closure $next) {
                abort_unless(config('updater.enabled'), 404);

                return $next($request);
            }),
        ];
    }

    public function index(): View
    {
        return view('settings.updater', [
            'check' => $this->updater->lastCheck(),
            'state' => $this->updater->publicState(),
            'log' => $this->updater->logTail(),
            'maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'state' => $this->updater->publicState(),
            'log' => $this->updater->logTail(),
            'maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function check(): RedirectResponse
    {
        $result = $this->updater->check();

        if ($result['error']) {
            return $this->back('danger', 'Prüfung fehlgeschlagen: '.$result['error']);
        }
        if ($result['fetch_error']) {
            return $this->back('warning', 'Das Repository konnte nicht abgefragt werden (Prüfung erfolgt zusätzlich per Scheduler): '.$result['fetch_error']);
        }

        return $result['behind'] > 0
            ? $this->back('info', $result['behind'].' neue(r) Commit(s) verfügbar.')
            : $this->back('success', 'Die Installation ist aktuell.');
    }

    public function start(Request $request): RedirectResponse
    {
        // Läuft die Anwendung bereits im Wartungsmodus (z. B. nach einem fehlgeschlagenen
        // Update), muss der Bypass zum bestehenden Geheimnis passen.
        $maintenance = app()->maintenanceMode();
        $secret = ($maintenance->active() ? ($maintenance->data()['secret'] ?? null) : null) ?: Str::random(40);

        try {
            $this->updater->request($request->user()->name, $request->boolean('full'), $secret);
        } catch (RuntimeException $e) {
            return $this->back('danger', $e->getMessage());
        }

        Log::info('Online-Update angefordert', ['user_id' => $request->user()->id, 'full' => $request->boolean('full')]);

        // Bypass-Cookie: Der Admin kann den Fortschritt auch im Wartungsmodus verfolgen.
        return $this->back('info', 'Das Update wurde angefordert und startet innerhalb einer Minute.')
            ->withCookie(MaintenanceModeBypassCookie::create($secret));
    }

    public function cancel(): RedirectResponse
    {
        return $this->updater->cancelRequest()
            ? $this->back('success', 'Die Update-Anforderung wurde zurückgenommen.')
            : $this->back('warning', 'Es ist kein Update angefordert (oder es läuft bereits).');
    }

    public function up(Request $request): RedirectResponse
    {
        if ($this->updater->isRunning()) {
            return $this->back('danger', 'Während eines laufenden Updates kann der Wartungsmodus nicht beendet werden.');
        }

        Artisan::call('up');
        Log::warning('Wartungsmodus manuell über den Online-Updater beendet', ['user_id' => $request->user()->id]);

        return $this->back('success', 'Der Wartungsmodus wurde beendet.');
    }

    private function back(string $type, string $message): RedirectResponse
    {
        return redirect()->route('updater.index')->with(['type' => $type, 'Meldung' => $message]);
    }
}
