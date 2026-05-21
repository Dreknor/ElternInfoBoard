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

                {{-- Tab-Navigation via jQuery-Shim (data-toggle="tab" + .nav-tabs) --}}
                <div class="border-b border-gray-200 overflow-x-auto">
                    <ul class="nav nav-tabs flex flex-nowrap min-w-max px-4" id="settings-tabs-nav" role="tablist">
                        @php
                            $tabs = [
                                ['id' => 'home',          'label' => 'Home',               'icon' => 'fas fa-home'],
                                ['id' => 'email',         'label' => 'Email',              'icon' => 'fas fa-envelope'],
                                ['id' => 'notify',        'label' => 'Benachrichtigungen', 'icon' => 'fas fa-bell'],
                                ['id' => 'schickzeiten',  'label' => 'Schickzeiten',       'icon' => 'fas fa-clock'],
                                ['id' => 'care',          'label' => 'Care',               'icon' => 'fas fa-heart'],
                                ['id' => 'keycloak',      'label' => 'OIDC',               'icon' => 'fas fa-key'],
                                ['id' => 'pflichtstunden','label' => 'Pflichtstunden',     'icon' => 'fas fa-tasks'],
                                ['id' => 'schoolyear',    'label' => 'Schuljahreswechsel', 'icon' => 'fas fa-graduation-cap'],
                                ['id' => 'stundenplan',   'label' => 'Stundenplan',        'icon' => 'fas fa-calendar-alt'],
                                ['id' => 'reminder',      'label' => 'Erinnerungen',       'icon' => 'fas fa-alarm-clock'],
                                ['id' => 'messenger',     'label' => 'Eltern-Nachrichten', 'icon' => 'fas fa-comments'],
                                ['id' => 'design',        'label' => 'Design',             'icon' => 'fas fa-palette'],
                            ];
                        @endphp
                        @foreach($tabs as $i => $tab)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $i === 0 ? 'active' : '' }}"
                                   id="{{ $tab['id'] }}-tab"
                                   data-toggle="tab"
                                   href="#settings-{{ $tab['id'] }}"
                                   role="tab"
                                   aria-controls="settings-{{ $tab['id'] }}"
                                   aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                                    <i class="{{ $tab['icon'] }} mr-1 opacity-75"></i>
                                    {{ $tab['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Tab-Inhalte --}}
                <div class="tab-content p-4">
                    <div class="tab-pane fade show active" id="settings-home" role="tabpanel">
                        @include('settings.tabs.home-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-email" role="tabpanel">
                        @include('settings.tabs.email-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-notify" role="tabpanel">
                        @include('settings.tabs.notify-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-schickzeiten" role="tabpanel">
                        @include('settings.tabs.schickzeiten-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-care" role="tabpanel">
                        @include('settings.tabs.care-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-keycloak" role="tabpanel">
                        @if(View::exists('settings.tabs.keycloak-tab'))
                            @include('settings.tabs.keycloak-tab')
                        @endif
                    </div>
                    <div class="tab-pane fade" id="settings-pflichtstunden" role="tabpanel">
                        @include('settings.tabs.pflichtstunden-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-schoolyear" role="tabpanel">
                        @include('settings.tabs.schoolyear-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-stundenplan" role="tabpanel">
                        @include('settings.tabs.stundenplan-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-reminder" role="tabpanel">
                        @include('settings.tabs.reminder-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-messenger" role="tabpanel">
                        @include('settings.tabs.messenger-tab')
                    </div>
                    <div class="tab-pane fade" id="settings-design" role="tabpanel">
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
        tinymce.init({
            selector: 'textarea:not(.no-tinymce)',
            lang: 'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink link charmap',
                'searchreplace visualblocks code',
                'insertdatetime paste code wordcount',
            ],
            toolbar: 'undo redo | formatselect | bold italic',
        });
    </script>
@endpush
