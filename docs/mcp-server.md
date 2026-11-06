# MCP-Server (Eltern, readonly)

Endpoint: `POST /api/v1/mcp` (Streamable HTTP, basiert auf `laravel/mcp`, Registrierung in `routes/ai.php`).

## Freischaltung

Der Server ist standardmäßig **aus** und antwortet dann mit 404. Freischaltung je Instanz in der `.env`
(`config/mcp_server.php`):

```dotenv
MCP_ENABLED=true
MCP_TOKEN_LIFETIME=60          # Minuten, 5–1440
MCP_DISABLED_TOOLS=            # z. B. list_krankmeldungen,list_krankmeldungen_history,get_messenger_messages
```

Mit `MCP_DISABLED_TOOLS` lassen sich einzelne Werkzeuge abschalten, etwa wenn Gesundheitsdaten (Krankmeldungen)
oder private Nachrichten nicht an einen Chatbot-Anbieter gehen sollen.

## Zugriff

- **Nur Bearer-Token.** Sitzungs-Cookies werden abgelehnt (401). Ohne Token → 401, bei erzwungenem Passwortwechsel → 403,
  deaktivierte Konten → 401.
- **Erlaubte Tokens:** App-Tokens (`*`) und kurzlebige **MCP-Tokens** (`mcp`, `mcp:read`). Integrations-Tokens
  (Home Assistant u. ä.) sind ausgeschlossen (403).
- **MCP-Token für einen Chatbot:** `POST /chat/mcp-token` (Web-Route, angemeldete Sitzung + CSRF-Token) liefert
  `{token, token_type: "Bearer", expires_at, mcp_url}`. Das Token
  - gilt nur für `POST /api/v1/mcp` – jede andere API-Route antwortet mit 403 `mcp_token_scope`
    (`App\Http\Middleware\RestrictMcpTokens`), auch `auth/web-link` und `token/refresh`;
  - läuft nach `MCP_TOKEN_LIFETIME` Minuten ab; je Nutzer bleiben höchstens fünf gültig, abgelaufene werden beim
    nächsten Abruf gelöscht;
  - kann nur aus einer Web-Sitzung erzeugt werden, nicht mit einem anderen Token.
- **Rate-Limit:** 60 MCP-Anfragen pro Minute und Nutzer (`throttle:mcp`); die internen Lese-Aufrufe zählen zusätzlich
  zum normalen API-Limit des Nutzers.

Beispiel im Snippet (läuft auf der eigenen Seite, z. B. `/chat`):

```js
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const res = await fetch('/chat/mcp-token', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
const { token, mcp_url, expires_at } = await res.json();
// token + mcp_url an den Chatbot-Anbieter übergeben; vor expires_at neu abrufen.
```

## Nutzerkontext und Schreibschutz

- Jedes Werkzeug (`app/Mcp/ToolCatalog.php`) bildet genau eine GET-Route der App-API ab und wird über
  `App\Mcp\ReadOnlyApiGateway` intern als der angemeldete Nutzer ausgeführt – inklusive Route-Middleware, Berechtigungen,
  Module und Policies. Sichtbar ist nur, was der Nutzer auch in App/Web sieht.
- Das Gateway lässt nur GET-Routen unter `api/` zu; alle Werkzeuge sind mit `readOnlyHint` annotiert. Jeder interne Aufruf
  läuft in einer Datenbank-Transaktion, die immer zurückgerollt wird – schreibt eine GET-Route doch etwas, wird es verworfen.
- Messenger-Nachrichten (`get_messenger_messages`) werden gelesen, ohne die Unterhaltung als gelesen zu markieren
  (Request-Attribut `read_only`). Anhänge erscheinen mit Name, Typ und Größe; Dateiinhalte werden nicht ausgeliefert.
- **Neues Werkzeug:** Eintrag in `ToolCatalog::all()` (die Route muss GET-only sein; `McpServerTest` prüft das).

## Hinweise für den Chatbot-Betrieb

- Ein Snippet, das in die Weboberfläche eingebunden ist, läuft mit allen Rechten der Sitzung des Nutzers
  (gleicher Ursprung). Die Beschränkung auf „nur lesen“ gilt für den MCP-Weg, nicht für fremdes JavaScript im Layout.
  Bevorzugt den Chatbot nur auf `/chat` laden bzw. als `<iframe>` von der Domain des Anbieters einbinden und nur das
  MCP-Token übergeben.
- Inhalte anderer Nutzer (Beiträge, Kommentare, Nachrichten) erreichen das Sprachmodell. Der Chatbot sollte keine
  weiteren Werkzeuge mit Außenwirkung haben und keine Bilder/Links aus Werkzeug-Antworten automatisch laden
  (Schutz vor Prompt-Injection und Datenabfluss).
- Die abgefragten Daten (bei Krankmeldungen Gesundheitsdaten nach Art. 9 DSGVO) gehen an den Chatbot-Anbieter –
  Auftragsverarbeitung klären oder Werkzeuge per `MCP_DISABLED_TOOLS` abschalten.
