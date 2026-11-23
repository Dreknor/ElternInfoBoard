{{-- Eigene Skripte für diesen Ausgabeort (passend zu Seite und Zielgruppe). Leer, solange CUSTOM_SCRIPTS_ENABLED nicht gesetzt ist. --}}
@if(\App\Services\CustomScripts\CustomScriptRenderer::enabled())
{!! app(\App\Services\CustomScripts\CustomScriptRenderer::class)->render($position) !!}
@endif
