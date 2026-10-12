# MCP-Server (Eltern, readonly)

Endpoint: `POST /api/v1/mcp` (Streamable HTTP, basiert auf `laravel/mcp`, Registrierung in `routes/ai.php`).

- **Authentifizierung:** Sanctum-Bearer-Token des Nutzers (z. B. `POST /api/v1/token`). Ohne Token → 401; bei erzwungenem Passwortwechsel → 403.
- **Nutzerkontext:** Jedes Werkzeug (`app/Mcp/ToolCatalog.php`) bildet genau eine GET-Route der App-API ab und wird über `App\Mcp\ReadOnlyApiGateway` intern als der angemeldete Nutzer ausgeführt – inklusive Route-Middleware, Berechtigungen, Module und Policies. Sichtbar ist nur, was der Nutzer auch in App/Web sieht.
- **Readonly:** Das Gateway lässt nur GET-Routen unter `api/` zu; alle Werkzeuge sind mit `readOnlyHint` annotiert. Messenger-Nachrichten (`get_messenger_messages`) werden gelesen, ohne die Unterhaltung als gelesen zu markieren: Das Gateway setzt das Request-Attribut `read_only`, das der Controller beachtet. Anhänge von Beiträgen und Nachrichten erscheinen mit Name, Typ und Größe (`get_post`, `list_files`, `get_messenger_messages`); Dateiinhalte werden nicht ausgeliefert.
- **Neues Werkzeug:** Eintrag in `ToolCatalog` hinzufügen (die Route muss GET-only sein; `McpServerTest` prüft das).
