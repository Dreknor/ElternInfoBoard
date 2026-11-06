<?php

namespace App\Http\Controllers;

use App\Services\CustomScripts\CustomScriptRenderer;
use Illuminate\Contracts\View\View;

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
}
