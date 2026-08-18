@extends('layouts.app')
@section('title') - Reinigung bearbeiten @endsection

@section('content')
    <div class="w-full max-w-4xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-4">
        <a href="{{url('reinigung')}}"
           class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
           style="background: var(--color-widget-primary-from);"
           onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-to')"
           onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-from')">
            <i class="fas fa-arrow-left"></i>
            zurück
        </a>

        <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border);">
                <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                    <i class="fas fa-broom"></i>
                    Reinigung {{$Bereich}}: {{$datum->format('d.m')}} - {{$ende->format('d.m.Y')}}
                </h5>
            </div>
            <div class="p-4">
                <form id="editform" action="{{url('reinigung/'.$Bereich)}}" method="post" class="space-y-3">
                    @csrf
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-text-primary);">
                            Familie
                        </label>
                        <select name="users_id" id="users_id" class="w-100 px-3 py-2 rounded-lg outline-none"
                                style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                            @foreach($users as $user)
                                <option value="{{$user->id}}">
                                    {{$user->name}} ({{$user->reinigung_period_count}} {{$user->reinigung_period_count == 1 ? 'Einsatz' : 'Einsätze'}})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-text-primary);">
                            Datum
                        </label>
                        <input class="w-100 px-3 py-2 rounded-lg outline-none" name="datum" type="date" readonly value="{{$datum->format('Y-m-d')}}"
                               style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                    </div>
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-text-primary);">
                            Aufgabe
                        </label>
                        <select name="aufgabe" class="w-100 px-3 py-2 rounded-lg outline-none"
                                style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                            @foreach($aufgaben as $task)
                                <option value="{{$task->id}}">
                                    {{$task->task}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="d-block mb-1" style="color: var(--color-text-primary);">
                            Bemerkung
                        </label>
                        <input type="text" name="bemerkung" class="w-100 px-3 py-2 rounded-lg outline-none"
                               style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);">
                    </div>
                </form>
            </div>
            <div class="px-4 py-3 border-t" style="border-color: var(--color-card-border);">
                <button type="submit" form="editform"
                        class="w-100 inline-flex items-center justify-content-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                        style="background: var(--color-widget-success-from);"
                        onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-to')"
                        onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-from')">
                    <i class="fas fa-save"></i>
                    speichern
                </button>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        /* Select2 an das Theme-Farbschema angleichen */
        .select2-container {
            z-index: 99999 !important;
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 2px solid var(--color-input-border) !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            background: var(--color-input-bg) !important;
            line-height: 26px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            color: var(--color-text-primary) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important;
            top: 1px !important;
        }

        .select2-dropdown {
            z-index: 99999 !important;
            border: 2px solid var(--color-widget-primary-from) !important;
            border-radius: 0.5rem !important;
            background: var(--color-card-bg) !important;
        }

        .select2-search--dropdown {
            padding: 8px !important;
            background: var(--color-card-bg) !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 2px solid var(--color-input-border) !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
            outline: none !important;
            background: var(--color-input-bg) !important;
            color: var(--color-text-primary) !important;
        }

        .select2-results__options {
            max-height: 300px !important;
            overflow-y: auto !important;
        }

        .select2-results {
            background: var(--color-card-bg) !important;
        }

        .select2-results__option {
            padding: 10px 12px !important;
            background: var(--color-card-bg) !important;
            color: var(--color-text-primary) !important;
        }

        .select2-results__option--highlighted {
            background: var(--color-widget-primary-from) !important;
            color: var(--color-widget-header-text) !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background: var(--color-widget-primary-to) !important;
            color: var(--color-widget-header-text) !important;
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Nutzerauswahl durchsuchbar machen; die Sortierung (wenigste
            // Einsätze im aktuellen Zeitraum zuerst, dann Name) erfolgt bereits
            // serverseitig in ReinigungController::create().
            $('#users_id').select2({
                placeholder: '🔍 Familie suchen und auswählen...',
                width: '100%',
                dropdownParent: $('body'),
                language: {
                    noResults: function () {
                        return '❌ Keine Familie gefunden';
                    },
                    searching: function () {
                        return '🔍 Suche...';
                    },
                },
            });
        });
    </script>
@endpush

