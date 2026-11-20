{{--
    Betrieb in einem Unterverzeichnis (z. B. https://schule.de/elterninfoboard/public/).

    Alles, was im Browser mit einem festen „/…“-Pfad arbeitet, zeigte sonst auf die
    Domain-Wurzel. Dieses Partial stellt den Basis-Pfad bereit und gehört in den
    <head> jedes Layouts, das die Vite-Assets oder Livewire nutzt.

    - window.appUrl('posts/1') baut URLs relativ zur Installation (für JavaScript).
    - Livewire nimmt die Update-URL vom ersten Element mit data-update-uri im Dokument;
      Livewire selbst schreibt dort „/livewire/update“ ohne Unterverzeichnis hinein.
    - Die Newsreader-Schrift wird hier statt in app.css eingebunden, weil Vite feste
      Pfade in CSS nicht um das Unterverzeichnis ergänzt.
--}}
@php($appBasePath = \App\Support\BasePath::get())
<meta name="app-base-path" content="{{ $appBasePath }}">
@if($appBasePath !== '')
    <meta name="livewire-update-uri" data-update-uri="{{ $appBasePath }}/livewire/update">
@endif
<script>
    window.appBasePath = @js($appBasePath);
    window.appUrl = function (path) {
        return window.appBasePath + '/' + String(path ?? '').replace(/^\/+/, '');
    };
</script>
<style>
    @font-face {
        font-family: 'Newsreader';
        src: url('{{ asset('fonts/Newsreader-VariableFont_opsz,wght.ttf') }}') format('truetype-variations');
        font-weight: 200 800;
        font-stretch: 75% 125%;
        font-style: normal;
        font-display: swap;
    }

    @font-face {
        font-family: 'Newsreader';
        src: url('{{ asset('fonts/Newsreader-Italic-VariableFont_opsz,wght.ttf') }}') format('truetype-variations');
        font-weight: 200 800;
        font-stretch: 75% 125%;
        font-style: italic;
        font-display: swap;
    }
</style>
