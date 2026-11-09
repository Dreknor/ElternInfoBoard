<?php

namespace App\Http\Middleware;

use App\Services\McpTokens;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MCP-Tokens (Chatbot) dürfen nur den MCP-Endpunkt aufrufen. Läuft für die gesamte API-Gruppe,
 * damit ein solches Token nie Profil, Krankmeldungen, Messenger o. Ä. direkt lesen oder ändern kann.
 *
 * Die internen Lese-Aufrufe des MCP-Gateways tragen kein Bearer-Token und laufen daher durch.
 */
class RestrictMcpTokens
{
    public const MCP_PATH = 'api/v1/mcp';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken() || $request->is(self::MCP_PATH)) {
            return $next($request);
        }

        if (McpTokens::isMcp($request->user('sanctum')?->currentAccessToken())) {
            return response()->json([
                'message' => 'Dieses Token ist nur für den MCP-Server gültig.',
                'code' => 'mcp_token_scope',
            ], 403);
        }

        return $next($request);
    }
}
