<?php

namespace App\Http\Middleware;

use App\Services\McpTokens;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zugang zum MCP-Server:
 * - nur, wenn er für die Instanz freigeschaltet ist (`MCP_ENABLED`), sonst 404 – keine Spur nach außen,
 * - nur mit Bearer-Token (keine Sitzungs-Cookies, damit fremde Seiten nichts im Namen des Nutzers abfragen),
 * - nur mit App- oder MCP-Token; Integrations-Tokens (Home Assistant u. ä.) sind ausgeschlossen.
 *
 * Steht vor `auth:sanctum`, damit eine deaktivierte Instanz auch ohne Token mit 404 antwortet.
 */
class EnsureMcpAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(McpTokens::enabled(), 404);

        $token = $request->user('sanctum')?->currentAccessToken();

        // Sitzung (Cookie) statt Token: nicht zulässig
        if ($token instanceof TransientToken) {
            return response()->json(['message' => 'Nicht authentifiziert.'], 401);
        }

        if ($token && ! McpTokens::mayUseServer($token)) {
            return response()->json([
                'message' => 'Dieses Token ist für den MCP-Server nicht freigegeben.',
                'code' => 'mcp_scope',
            ], 403);
        }

        return $next($request);
    }
}
