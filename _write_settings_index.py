content = """@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3"
     x-data="adminSettings()"
     x-init="init()">

    <div class="rounded-xl shadow-lg overflow-hidden" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);">

        <div class="px-5 py-3 border-b flex items-center gap-3" style="background-color: var(--color-primary); border-color: var(--color-primary-dark);">
            <div class="flex items-center justify-center w-9 h-9 bg-white/20 rounded-lg">
                <i class="fas fa-cog text-white text-lg"></i>
            </div>
            <div>
                <h5 class="text-lg font-bold text-white mb-0">Einstellungen</h5>
                <p class="text-xs text-white/70 mb-0">Systemweite Konfiguration</p>
            </div>
        </div>

        <div class="flex" style="min-height: 600px;">

            <nav class="flex-shrink-0 border-r" style="width: 240px; background-color: var(--color-body-bg); border-color: var(--color-card-border);">
                <div class="p-2 space-y-0.5">
                    @php
                        $tabs = [
                            ['id' => 'home',          'label' => 'Allgemein',          'icon' => 'fas fa-home'],
                            ['id' => 'email',         'label' => 'E-Mail / SMTP',      'icon' => 'fas fa-envelope'],
                            ['id' => 'notify',        'label' => 'Benachrichtigungen', 'icon' => 'fas fa-bell'],
                            ['id' => 'schickzeiten',  'label' => 'Schickzeiten',       'icon' => 'fas fa-clock'],
                            ['id' => 'care',          'label' => 'Care',               'icon' => 'fas fa-heart'],
                            ['id' => 'keycloak',      'label' => 'OIDC / Keycloak',   'icon' => 'fas fa-key'],
                            ['id' => 'pflichtstunden','label' => 'Pflichtstunden',     'icon' => 'fas fa-tasks'],
                            ['id' => 'schoolyear',    'label' => 'Schuljahreswechsel', 'icon' => 'fas fa-graduation-cap'],
                            ['id' => 'stundenplan',   'label' => 'Stundenplan',        'icon' => 'fas fa-calendar-alt'],
                            ['id' => 'reminder',      'label' => 'Erinnerungen',       'icon' => 'fas fa-alarm-clock'],
                            ['id' => 'messenger',     'label' => 'Eltern-Nachrichten', 'icon' => 'fas fa-comments'],
                            ['id' => 'design',        'label' => 'Design',             'icon' => 'fas fa-palette'],
                        ];
                    @endphp

                    @foreach($tabs as $tab)
                        <button
                            @click="activeTab = '{{ $tab['id'] }}'"
                            :class="activeTab === '{{ $tab['id'] }}' ? 'font-semibold' : 'font-medium hover:bg-black/5 dark:hover:bg-white/5'"
                            :style="activeTab === '{{ $tab['id'] }}' ? 'background-color: var(--color-primary); color: white;' : 'color: var(--color-text-primary);'"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left text-sm transition-all duration-150">
                            <i class="{{ $tab['icon'] }} w-4 text-center text-sm flex-shrink-0"
                               :style="activeTab === '{{ $tab['id'] }}' ? 'color: white;' : 'color: var(--color-primary);'"></i>
                            <span class="leading-tight">{{ $tab['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </nav>

            <div class="flex-1 overflow-y-auto" id="settings-main-content" style="max-height: calc(100vh - 160px);">
                <div x-show="activeTab === 'home'" x-cloak class="p-6">
                    @include('settings.tabs.home-tab')
                </div>
                <div x-show="activeTab === 'email'" x-cloak class="p-6">
                    @include('settings.tabs.email-tab')
                </div>
                <div x-show="activeTab === 'notify'" x-cloak class="p-6">
                    @include('settings.tabs.notify-tab')
                </div>
                <div x-show="activeTab === 'schickzeiten'" x-cloak class="p-6">
                    @include('settings.tabs.schickzeiten-tab')
                </div>
                <div x-show="activeTab === 'care'" x-cloak class="p-6">
                    @include('settings.tabs.care-tab')
                </div>
                <div x-show="activeTab === 'keycloak'" x-cloak class="p-6">
                    @if(View::exists('settings.tabs.keycloak-tab'))
                        @include('settings.tabs.keycloak-tab')
                    @endif
                </div>
                <div x-show="activeTab === 'pflichtstunden'" x-cloak class="p-6">
                    @include('settings.tabs.pflichtstunden-tab')
                </div>
                <div x-show="activeTab === 'schoolyear'" x-cloak class="p-6">
                    @include('settings.tabs.schoolyear-tab')
                </div>
                <div x-show="activeTab === 'stundenplan'" x-cloak class="p-6">
                    @include('settings.tabs.stundenplan-tab')
                </div>
                <div x-show="activeTab === 'reminder'" x-cloak class="p-6">
                    @include('settings.tabs.reminder-tab')
                </div>
                <div x-show="activeTab === 'messenger'" x-cloak class="p-6">
                    @include('settings.tabs.messenger-tab')
                </div>
                <div x-show="activeTab === 'design'" x-cloak class="p-6">
                    @include('settings.tabs.design-tab')
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
        function adminSettings() {
            return {
                activeTab: 'home',
                init() {
                    const hash = window.location.hash?.slice(1);
                    const validTabs = ['home','email','notify','schickzeiten','care','keycloak',
                                       'pflichtstunden','schoolyear','stundenplan','reminder','messenger','design'];
                    if (hash && validTabs.includes(hash)) {
                        this.activeTab = hash;
                    }
                    this.$watch('activeTab', val => {
                        history.replaceState(null, '', '#' + val);
                    });
                    this.$nextTick(() => {
                        if (window.tinymce) {
                            tinymce.init({
                                selector: '#settings-main-content textarea:not(.no-tinymce)',
                                lang: 'de',
                                height: 400,
                                menubar: true,
                                plugins: ['advlist autolink link charmap','searchreplace visualblocks code','insertdatetime paste code wordcount'],
                                toolbar: 'undo redo | formatselect | bold italic',
                            });
                        }
                    });
                }
            };
        }
    </script>
@endpush

@push('css')
<style>
    [x-cloak] { display: none !important; }
    /* Bootstrap .tab-pane CSS-Hiding neutralisieren – Alpine.js steuert die Sichtbarkeit */
    #settings-main-content .tab-pane { display: block !important; }
</style>
@endpush
"""

with open('D:/www/elterninfo/resources/views/settings/index.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Written:", len(content), "chars")

