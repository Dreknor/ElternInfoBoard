<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stellt sicher, dass eingeloggte Nutzer eine E-Mail-Adresse hinterlegt haben.
 *
 * Betrifft ausschließlich Nutzer, die via UCS@school provisioniert wurden
 * (ucs_source = 'kelvin') und deren E-Mail-Feld leer ist.
 *
 * Beim ersten Aufruf einer geschützten Seite ohne E-Mail wird der Nutzer
 * auf die Einstellungsseite (/einstellungen) weitergeleitet, wo er eine
 * E-Mail-Adresse eintragen kann.
 *
 * Ausnahmen (kein Redirect):
 *   – Nicht eingeloggte Nutzer (kein Auth-User)
 *   – Nutzer mit gesetzter E-Mail-Adresse
 *   – Nutzer die NICHT via UCS provisioniert wurden (ucs_source != 'kelvin')
 *   – Die Einstellungsseite selbst (vermeidet Redirect-Loop)
 *   – Logout-Routen
 *   – JSON/API-Requests
 */
class EnsureUserHasEmail
{
    /**
     * URL-Präfixe und Pfade, die ausgenommen sind.
     * @var list<string>
     */
    private const ALLOWED_PATHS = [
        'einstellungen',
        'logout',
        'auth/ucs',
        'login',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Nur eingeloggte Nutzer prüfen
        if (! auth()->check()) {
            return $next($request);
        }

        // API / AJAX-Anfragen nicht blockieren
        if ($request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }

        /** @var \App\Model\User $user */
        $user = auth()->user();

        // Nur Nutzer mit leerem E-Mail-Feld UND UCS-Herkunft betreffen
        if (! empty($user->email) || $user->ucs_source !== 'kelvin') {
            return $next($request);
        }

        // Ausnahme-Pfade durchlassen
        foreach (self::ALLOWED_PATHS as $path) {
            if ($request->is($path) || $request->is($path.'/*')) {
                return $next($request);
            }
        }

        // Zur Einstellungsseite umleiten
        return redirect()->route('einstellungen')->with([
            'type'    => 'warning',
            'Meldung' => 'Bitte hinterlegen Sie eine E-Mail-Adresse, um alle Funktionen nutzen zu können.',
        ]);
    }
}


