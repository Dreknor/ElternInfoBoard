<?php

namespace App\Mcp\Servers;

use App\Mcp\ToolCatalog;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Contracts\Transport;

#[Name('ElternInfoBoard – Eltern')]
#[Version('1.0.0')]
#[Instructions('Lesender Zugriff auf die Elternfunktionen des ElternInfoBoards im Kontext des angemeldeten Nutzers. Es sind nur Informationen abrufbar, die der Nutzer auch in der App oder im Web-Interface sieht. Änderungen sind nicht möglich. Kinder-IDs liefert list_children.')]
class ElternServer extends Server
{
    protected array $resources = [];

    protected array $prompts = [];

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->tools = ToolCatalog::tools();
    }
}

