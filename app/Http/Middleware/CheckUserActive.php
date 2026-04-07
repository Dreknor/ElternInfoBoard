<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prüft ob der eingeloggte Benutzer aktiv ist.
 * Deaktivierte Benutzer werden ausgeloggt und zur Login-Seite weitergeleitet.
 */
class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // Explizites === false: Nutzer mit NULL (Legacy/Factory) gelten als aktiv
        if (auth()->check() && auth()->user()->is_active === false) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Ihr Konto wurde deaktiviert. Bitte wenden Sie sich an die Verwaltung.',
            ]);
        }

        return $next($request);
    }
}


