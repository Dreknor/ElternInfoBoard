<?php

namespace App\Http\Controllers;

use App\Services\CustomScripts\CustomScriptRenderer;
use App\Services\McpTokens;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Schlanke Seite, die nur das instanzspezifische Snippet (z. B. Chatbot) lädt.
 * Die App öffnet sie über auth/web-link im In-App-Browser.
 */
class ChatController extends Controller
{
    public function show(CustomScriptRenderer $renderer): View
    {
        $snippet = $renderer->chatPageSnippet();

        abort_if($snippet === null, 404);

        return view('chat', ['snippet' => $snippet]);
    }

    /**
     * Kurzlebiges MCP-Token für den Chatbot des angemeldeten Nutzers.
     *
     * Aufruf aus dem Snippet per `fetch` mit CSRF-Token (Sitzung). Das Token gilt nur für
     * `POST /api/v1/mcp`, nur lesend, und läuft nach `mcp_server.token_lifetime` Minuten ab.
     * Andere Seiten können es nicht abholen: CSRF-Schutz und keine CORS-Freigabe für Web-Routen.
     */
    public function mcpToken(Request $request): JsonResponse
    {
        abort_unless(McpTokens::enabled(), 404);

        $token = McpTokens::create($request->user());

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'mcp_url' => url('api/v1/mcp'),
        ])->header('Cache-Control', 'no-store');
    }
}
