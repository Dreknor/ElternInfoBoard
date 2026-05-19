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

                {{-- Alpine.js Tab-System --}}
                <div x-data="{
                    activeTab: (function(){
                        var h = window.location.hash.replace('#','');
                        var valid = ['home','email','notify','schickzeiten','care','keycloak','pflichtstunden','schoolyear','stundenplan','reminder','messenger'];
                        return valid.indexOf(h) !== -1 ? h : 'home';
                    })(),
                    setTab(tab) {
                        this.activeTab = tab;
                    }
                }">
                    {{-- Tab Navigation --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 px-4 overflow-x-auto">
                        <ul class="flex gap-1 min-w-max" role="tablist">
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
                                            :aria-selected="activeTab === '{{ $tab['id'] }}'"
                                            @click="setTab('{{ $tab['id'] }}')"
                                            :class="activeTab === '{{ $tab['id'] }}'
                                                ? 'border-b-2 border-blue-600 text-blue-700 dark:text-blue-400 dark:border-blue-400 bg-blue-50/50 dark:bg-blue-900/20'
                                                : 'border-b-2 border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300'"
                                            class="inline-flex items-center gap-1.5 px-3 py-3 text-sm font-medium transition-all duration-150 whitespace-nowrap cursor-pointer focus:outline-none">
                                        <i class="{{ $tab['icon'] }} text-xs opacity-75"></i>
                                        {{ $tab['label'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Tab Content --}}
                    <div class="p-4">
                        <div x-show="activeTab === 'home'">
                            @include('settings.tabs.home-tab')
                        </div>
                        <div x-show="activeTab === 'email'" style="display:none">
                            @include('settings.tabs.email-tab')
                        </div>
                        <div x-show="activeTab === 'notify'" style="display:none">
                            @include('settings.tabs.notify-tab')
                        </div>
                        <div x-show="activeTab === 'schickzeiten'" style="display:none">
                            @include('settings.tabs.schickzeiten-tab')
                        </div>
                        <div x-show="activeTab === 'care'" style="display:none">
                            @include('settings.tabs.care-tab')
                        </div>
                        <div x-show="activeTab === 'keycloak'" style="display:none">
                            {{-- Keycloak/OIDC Tab falls vorhanden --}}
                            @if(View::exists('settings.tabs.keycloak-tab'))
                                @include('settings.tabs.keycloak-tab')
                            @endif
                        </div>
                        <div x-show="activeTab === 'pflichtstunden'" style="display:none">
                            @include('settings.tabs.pflichtstunden-tab')
                        </div>
                        <div x-show="activeTab === 'schoolyear'" style="display:none">
                            @include('settings.tabs.schoolyear-tab')
                        </div>
                        <div x-show="activeTab === 'stundenplan'" style="display:none">
                            @include('settings.tabs.stundenplan-tab')
                        </div>
                        <div x-show="activeTab === 'reminder'" style="display:none">
                            @include('settings.tabs.reminder-tab')
                        </div>
                        <div x-show="activeTab === 'messenger'" style="display:none">
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
    </script>
@endpush
