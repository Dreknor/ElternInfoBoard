@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title flex items-center gap-2">
                    <i class="fas fa-cog text-blue-600"></i>
                    Einstellungen
                </h5>
            </div>
            <div class="card-body p-0">

                {{-- Tab-System via vanilla JS (kein Alpine nötig) --}}
                <div id="settings-tabs">
                    {{-- Tab Navigation --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 px-4 overflow-x-auto">
                        <ul class="flex gap-1 min-w-max" role="tablist" id="settings-tab-nav">
                            @php
                                $tabs = [
                                    ['id' => 'home',         'label' => 'Home',                'icon' => 'fas fa-home'],
                                    ['id' => 'email',        'label' => 'Email',               'icon' => 'fas fa-envelope'],
                                    ['id' => 'notify',       'label' => 'Benachrichtigungen',  'icon' => 'fas fa-bell'],
                                    ['id' => 'schickzeiten', 'label' => 'Schickzeiten',        'icon' => 'fas fa-clock'],
                                    ['id' => 'care',         'label' => 'Care',                'icon' => 'fas fa-heart'],
                                    ['id' => 'keycloak',     'label' => 'OIDC',                'icon' => 'fas fa-key'],
                                    ['id' => 'pflichtstunden','label' => 'Pflichtstunden',     'icon' => 'fas fa-tasks'],
                                    ['id' => 'schoolyear',   'label' => 'Schuljahreswechsel', 'icon' => 'fas fa-graduation-cap'],
                                    ['id' => 'stundenplan',  'label' => 'Stundenplan',         'icon' => 'fas fa-calendar-alt'],
                                    ['id' => 'reminder',     'label' => 'Erinnerungen',        'icon' => 'fas fa-alarm-clock'],
                                    ['id' => 'messenger',    'label' => 'Eltern-Nachrichten',  'icon' => 'fas fa-comments'],
                                ];
                            @endphp

                            @foreach($tabs as $tab)
                                <li role="presentation">
                                    <button type="button"
                                            role="tab"
                                            data-settings-tab="{{ $tab['id'] }}"
                                            class="settings-tab-btn inline-flex items-center gap-1.5 px-3 py-3 text-sm font-medium transition-all duration-150 whitespace-nowrap cursor-pointer focus:outline-none border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300">
                                        <i class="{{ $tab['icon'] }} text-xs opacity-75"></i>
                                        {{ $tab['label'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Tab Content --}}
                    <div class="p-4">
                        <div id="settings-panel-home" class="settings-tab-panel">
                            @include('settings.tabs.home-tab')
                        </div>
                        <div id="settings-panel-email" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.email-tab')
                        </div>
                        <div id="settings-panel-notify" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.notify-tab')
                        </div>
                        <div id="settings-panel-schickzeiten" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.schickzeiten-tab')
                        </div>
                        <div id="settings-panel-care" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.care-tab')
                        </div>
                        <div id="settings-panel-keycloak" class="settings-tab-panel" style="display:none">
                            @if(View::exists('settings.tabs.keycloak-tab'))
                                @include('settings.tabs.keycloak-tab')
                            @endif
                        </div>
                        <div id="settings-panel-pflichtstunden" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.pflichtstunden-tab')
                        </div>
                        <div id="settings-panel-schoolyear" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.schoolyear-tab')
                        </div>
                        <div id="settings-panel-stundenplan" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.stundenplan-tab')
                        </div>
                        <div id="settings-panel-reminder" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.reminder-tab')
                        </div>
                        <div id="settings-panel-messenger" class="settings-tab-panel" style="display:none">
                            @include('settings.tabs.messenger-tab')
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/piexif.min.js" type="text/javascript"></script>

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>
        tinymce.init({
            selector: 'textarea:not(.no-tinymce)',
            lang: 'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink link charmap',
                'searchreplace visualblocks code',
                'insertdatetime paste code wordcount',
                'contextmenu textcolor',
            ],
            toolbar: 'undo redo | formatselect | bold italic',
            contextmenu: "link inserttable | cell row column deletetable",
        });

        // ── Settings-Tab-Switching (vanilla JS, kein Alpine nötig) ───────────
        (function () {
            var ACTIVE_BTN   = ['border-b-2', 'border-blue-600', 'text-blue-700', 'bg-blue-50'];
            var INACTIVE_BTN = ['border-b-2', 'border-transparent', 'text-gray-600'];

            function activateTab(tabId) {
                // Alle Panels verstecken
                document.querySelectorAll('.settings-tab-panel').forEach(function (p) {
                    p.style.display = 'none';
                });
                // Alle Buttons deaktivieren
                document.querySelectorAll('.settings-tab-btn').forEach(function (b) {
                    b.classList.remove.apply(b.classList, ACTIVE_BTN);
                    INACTIVE_BTN.forEach(function (c) { b.classList.add(c); });
                    b.removeAttribute('aria-selected');
                });
                // Ziel-Panel anzeigen
                var panel = document.getElementById('settings-panel-' + tabId);
                if (panel) panel.style.display = '';
                // Aktiven Button hervorheben
                var btn = document.querySelector('[data-settings-tab="' + tabId + '"]');
                if (btn) {
                    INACTIVE_BTN.forEach(function (c) { btn.classList.remove(c); });
                    ACTIVE_BTN.forEach(function (c) { btn.classList.add(c); });
                    btn.setAttribute('aria-selected', 'true');
                }
                // TinyMCE in sichtbarem Panel neu berechnen (falls nötig)
                if (typeof tinymce !== 'undefined') {
                    setTimeout(function () {
                        tinymce.editors.forEach(function (ed) { ed.fire('resize'); });
                    }, 50);
                }
            }

            // Klick-Handler für alle Tab-Buttons registrieren
            document.querySelectorAll('.settings-tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activateTab(this.dataset.settingsTab);
                });
            });

            // Initialen Tab aktivieren (aus URL-Hash oder Standard: home)
            var validTabs = ['home','email','notify','schickzeiten','care','keycloak',
                             'pflichtstunden','schoolyear','stundenplan','reminder','messenger'];
            var initialTab = window.location.hash.replace('#', '');
            activateTab(validTabs.indexOf(initialTab) !== -1 ? initialTab : 'home');
        })();
    </script>
@endpush
