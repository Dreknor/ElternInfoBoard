<?php

/*
|--------------------------------------------------------------------------
| MCP-Server für Eltern (readonly)
|--------------------------------------------------------------------------
|
| Der MCP-Endpunkt `/api/v1/mcp` ist standardmäßig aus und wird je Instanz in der `.env`
| freigeschaltet (z. B. für einen Chatbot). Ohne Freischaltung antwortet er mit 404, und
| es werden keine MCP-Tokens ausgegeben.
|
*/

return [

    'enabled' => (bool) env('MCP_ENABLED', false),

    /** Laufzeit der Chatbot-Tokens (`POST /chat/mcp-token`) in Minuten (5–1440). */
    'token_lifetime' => (int) env('MCP_TOKEN_LIFETIME', 60),

    /**
     * Werkzeuge, die auf dieser Instanz nicht angeboten werden (Namen aus `App\Mcp\ToolCatalog`),
     * z. B. „list_krankmeldungen,list_krankmeldungen_history,get_messenger_messages“, wenn
     * Gesundheitsdaten oder private Nachrichten nicht an einen Chatbot-Anbieter gehen sollen.
     */
    'disabled_tools' => array_values(array_filter(array_map('trim', explode(',', (string) env('MCP_DISABLED_TOOLS', ''))))),

];
