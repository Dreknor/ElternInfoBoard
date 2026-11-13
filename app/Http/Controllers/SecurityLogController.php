<?php

namespace App\Http\Controllers;

use App\Model\SecurityEvent;
use App\Model\User;
use App\Services\Security\SecurityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Nur-Lese-Ansicht des Sicherheitsprotokolls.
 * Bewusst ohne Lösch- und Exportfunktion (Manipulationsschutz, Datensparsamkeit).
 */
class SecurityLogController extends Controller
{
    /** Deutsche Kurzbezeichnungen der Ereignisse; unbekannte werden roh angezeigt. */
    public const EVENT_LABELS = [
        'auth.login' => 'Anmeldung',
        'auth.failed' => 'Anmeldung fehlgeschlagen',
        'auth.lockout' => 'Anmeldung gesperrt (zu viele Versuche)',
        'auth.logout' => 'Abmeldung',
        'auth.password_reset' => 'Passwort zurückgesetzt',
        'auth.magic_link.requested' => 'Anmeldelink angefordert',
        'auth.magic_link.used' => 'Anmeldelink verwendet',
        'auth.magic_link.failed' => 'Anmeldelink ungültig',
        'token.issued' => 'API-Token ausgestellt',
        'token.revoked' => 'API-Token widerrufen',
        'token.refreshed' => 'API-Token erneuert',
        'impersonation.start' => 'Anmeldung als anderer Nutzer gestartet',
        'impersonation.stop' => 'Anmeldung als anderer Nutzer beendet',
        'rbac.role_assigned' => 'Rolle zugewiesen',
        'rbac.role_removed' => 'Rolle entzogen',
        'rbac.permission_granted' => 'Recht erteilt',
        'rbac.permission_revoked' => 'Recht entzogen',
        'account.password_changed' => 'Passwort geändert',
        'account.password_reset_by_admin' => 'Passwort durch Administrator zurückgesetzt',
        'logs.deleted' => 'System-Logs gelöscht',
        'export.download' => 'Export heruntergeladen',
        'media.download' => 'Mediendatei heruntergeladen',
        'inquiry.file_download' => 'Datei einer Elternabfrage heruntergeladen',
        'mcp.tool_call' => 'MCP-Werkzeug aufgerufen',
        'ical.fetch' => 'Kalender-Feed abgerufen',
        'privacy.export' => 'Selbstauskunft heruntergeladen',
        'security_log.viewed' => 'Sicherheitsprotokoll eingesehen',
    ];

    public static function eventLabel(string $event): string
    {
        return self::EVENT_LABELS[$event] ?? $event;
    }

    public function index(Request $request)
    {
        $this->recordView($request);

        $query = SecurityEvent::query()->orderByDesc('created_at')->orderByDesc('id');

        // Ereignis-Präfix (z. B. "auth" oder "auth.login")
        if ($request->filled('event') && is_string($request->input('event'))) {
            $prefix = trim($request->input('event'));
            $escaped = addcslashes($prefix, '\\%_');
            $query->where(function ($q) use ($prefix, $escaped) {
                $q->where('event', $prefix)->orWhere('event', 'like', $escaped.'.%');
            });
        }

        if ($request->filled('user_id') && ctype_digit((string) $request->input('user_id'))) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('from') && ($from = $this->parseDate($request->input('from')))) {
            $query->where('created_at', '>=', $from->startOfDay());
        }
        if ($request->filled('to') && ($to = $this->parseDate($request->input('to')))) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        if ($request->filled('ip') && is_string($request->input('ip'))) {
            $query->where('ip_address', 'like', addcslashes(trim($request->input('ip')), '\\%_').'%');
        }

        $events = $query->paginate(50)->withQueryString();

        // Konten ggf. bereits gelöscht: dann bleibt nur die ID
        $ids = $events->getCollection()->flatMap(fn ($e) => [$e->user_id, $e->actor_id])->filter()->unique()->values();
        $names = User::withTrashed()->whereIn('id', $ids)->pluck('name', 'id');

        return view('security-log.index', [
            'events' => $events,
            'names' => $names,
            'eventOptions' => $this->eventOptions(),
            'retentionDays' => (int) config('security_log.retention_days'),
            'anonymizeAfterDays' => (int) config('security_log.anonymize_ip_after_days'),
        ]);
    }

    /** Auswahlliste: Gruppen-Präfixe (z. B. "auth") und die vorhandenen Einzelereignisse. */
    private function eventOptions(): array
    {
        $events = SecurityEvent::query()->distinct()->orderBy('event')->pluck('event');

        $options = [];
        foreach ($events->map(fn ($e) => explode('.', $e)[0])->unique() as $group) {
            $options[$group] = $group.' (alle)';
        }
        foreach ($events as $event) {
            $options[$event] = $event.' – '.self::eventLabel($event);
        }

        return $options;
    }

    /** Einsichtnahme protokollieren, höchstens einmal pro Nutzer und Stunde. */
    private function recordView(Request $request): void
    {
        $userId = $request->user()?->id;
        if ($userId === null) {
            return;
        }

        if (Cache::add('security_log.viewed.'.$userId, true, now()->addHour())) {
            SecurityLog::record('security_log.viewed', $userId);
        }
    }

    private function parseDate($value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
