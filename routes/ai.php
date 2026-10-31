<?php

use App\Mcp\Servers\ElternServer;
use Laravel\Mcp\Facades\Mcp;

/*
| MCP-Server für Eltern (readonly). Nur mit gültigem Sanctum-Token (Bearer) erreichbar; alle Werkzeuge laufen
| im Kontext dieses Nutzers. Ein erzwungener Passwortwechsel sperrt den Zugriff wie bei der App-API.
*/
Mcp::web('/api/v1/mcp', ElternServer::class)->middleware(['auth:sanctum', 'api.password']);
