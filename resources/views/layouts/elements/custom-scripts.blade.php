{{-- Instanzspezifisches Snippet (z. B. Chatbot). Leer, solange CUSTOM_SCRIPTS_ENABLED nicht gesetzt ist. --}}
@if(\App\Services\CustomScripts\CustomScriptRenderer::enabled())
{!! app(\App\Services\CustomScripts\CustomScriptRenderer::class)->render($position) !!}
@endif
