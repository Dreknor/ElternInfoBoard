<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSRF-Schutz für zustandsändernde GET-Routen.
 *
 * Laravels CSRF-Token greift nur bei POST/PUT/DELETE. Einige ältere Aktionen (Beitrag freigeben,
 * Modul umschalten, Liste archivieren …) werden jedoch per Link (GET) ausgelöst. Damit ein
 * präparierter Link von außen (E-Mail, fremde Webseite) solche Aktionen nicht im Namen eines
 * angemeldeten Nutzers auslösen kann, muss die Anfrage nachweislich aus der Anwendung selbst kommen:
 *
 *  - moderne Browser senden `Sec-Fetch-Site`; erlaubt ist nur `same-origin`
 *  - andernfalls muss der `Referer` (bzw. `Origin`) auf den eigenen Host zeigen
 *
 * Langfristig sollten diese Routen auf POST umgestellt werden.
 */
class RequireSameOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests() && ! config('app.enforce_same_origin_in_tests', false)) {
            return $next($request);
        }

        abort_unless($this->isSameOrigin($request), 403, 'Diese Aktion kann nur aus der Anwendung heraus ausgelöst werden.');

        return $next($request);
    }

    private function isSameOrigin(Request $request): bool
    {
        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        if ($fetchSite !== null) {
            return strtolower($fetchSite) === 'same-origin';
        }

        $source = $request->headers->get('Origin') ?: $request->headers->get('Referer');
        if (! $source) {
            return false;
        }

        $host = parse_url($source, PHP_URL_HOST);
        $port = parse_url($source, PHP_URL_PORT);

        return is_string($host)
            && strtolower($host) === strtolower($request->getHost())
            && ($port === null || (int) $port === (int) $request->getPort());
    }
}
