<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Session\Middleware\AuthenticateSession as BaseAuthenticateSession;

/**
 * Beendet Web-Sitzungen, deren Passwort-Hash nicht mehr zum Konto passt (Passwort wurde an anderer
 * Stelle geändert oder zurückgesetzt). Die eigene Sitzung übernimmt nach der Änderung den neuen Hash.
 *
 * Zusätzlich wird gemerkt, zu welchem Konto der gespeicherte Hash gehört: Wird der Nutzer ohne
 * Auth::login() gewechselt (z. B. Guard::setUser), ist der alte Hash nicht „veraltet“, sondern
 * gehört einem anderen Konto – dann wird er neu gesetzt statt abzumelden. Für eine übernommene
 * Sitzung desselben Kontos (der eigentliche Schutzzweck) ändert das nichts.
 */
class AuthenticateSession extends BaseAuthenticateSession
{
    public function handle($request, Closure $next)
    {
        // Nur für Sitzungs-Guards sinnvoll (Token-Guards kennen weder Sitzung noch „Angemeldet bleiben“).
        if (! $this->auth->guard() instanceof SessionGuard) {
            return $next($request);
        }

        if ($request->hasSession() && ($user = $request->user())) {
            $owner = $request->session()->get($this->ownerKey());

            if ($owner !== null && (string) $owner !== (string) $user->getAuthIdentifier()) {
                $request->session()->forget('password_hash_'.$this->auth->getDefaultDriver());
            }
        }

        return parent::handle($request, $next);
    }

    protected function storePasswordHashInSession($request)
    {
        parent::storePasswordHashInSession($request);

        if ($request->user()) {
            $request->session()->put($this->ownerKey(), $request->user()->getAuthIdentifier());
        }
    }

    private function ownerKey(): string
    {
        return 'password_hash_owner_'.$this->auth->getDefaultDriver();
    }
}
