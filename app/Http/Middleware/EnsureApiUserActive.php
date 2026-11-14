<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deaktivierte Konten dürfen auch die API nicht mehr nutzen
 * (Gegenstück zu CheckUserActive, das nur für Web-Sitzungen läuft).
 *
 * Tokens deaktivierter Konten lehnt bereits Sanctum ab (AppServiceProvider); diese Prüfung greift
 * zusätzlich für API-Aufrufe über eine bestehende Web-Sitzung (Sanctum „stateful“).
 */
class EnsureApiUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Explizites === false: Nutzer mit NULL (Legacy/Factory) gelten als aktiv
        if ($user && $user->is_active === false) {
            return response()->json([
                'success' => false,
                'message' => 'Ihr Konto wurde deaktiviert. Bitte wenden Sie sich an die Verwaltung.',
                'code' => 'account_disabled',
            ], 403);
        }

        return $next($request);
    }
}
