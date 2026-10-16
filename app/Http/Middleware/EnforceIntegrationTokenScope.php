<?php

namespace App\Http\Middleware;

use App\Services\IntegrationTokens;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integrations-Tokens (Home Assistant u. ä.) dürfen nur lesen und in den beim Erstellen
 * gewählten Bereichen schreiben. Läuft für die gesamte API-Gruppe, damit kein Endpunkt
 * vergessen werden kann; App-Tokens und Web-Sitzungen sind nicht betroffen.
 */
class EnforceIntegrationTokenScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $next($request);
        }

        $token = $request->user('sanctum')?->currentAccessToken();

        if (IntegrationTokens::isIntegration($token) && ! IntegrationTokens::allows($token, $request)) {
            return response()->json([
                'message' => 'Dieses Integrations-Token hat keine Berechtigung für diese Aktion.',
                'code' => 'integration_scope',
            ], 403);
        }

        return $next($request);
    }
}
