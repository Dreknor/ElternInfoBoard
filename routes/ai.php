<?php

use App\Http\Middleware\EnsureMcpAccess;
use App\Mcp\Servers\ElternServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

/*
| MCP-Server für Eltern (readonly), nur auf freigeschalteten Instanzen (MCP_ENABLED), sonst 404.
| Hinweis: laravel/mcp lädt diese Datei ohne Middleware-Gruppe (weder „web“ noch „api“) – alles Nötige steht hier.
| Nur mit Bearer-Token (App-Token oder kurzlebiges MCP-Token aus POST /chat/mcp-token), keine Sitzungs-Cookies,
| keine Integrations-Tokens. Alle Werkzeuge laufen im Kontext dieses Nutzers. Ein erzwungener Passwortwechsel
| sperrt den Zugriff wie bei der App-API.
*/
Route::middleware(EnsureMcpAccess::class)->group(function () {
    Mcp::web('/api/v1/mcp', ElternServer::class)
        ->middleware(['auth:sanctum', 'api.password', 'throttle:mcp']);
});
