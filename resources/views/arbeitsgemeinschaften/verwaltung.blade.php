@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3 border-b border-purple-800">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <h4 class="text-xl font-bold text-white mb-0 flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        Aktive Arbeitsgemeinschaften
                    </h4>
                    <a href="{{ route('verwaltung.arbeitsgemeinschaften.create') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-purple-700 font-semibold rounded-lg transition-colors duration-200 shadow-md"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="Neue AG erstellen">
                        <i class="fas fa-plus"></i>
                        <span>Neue AG erstellen</span>
                    </a>
                </div>
            </div>
            <div class="p-4">
                <!-- Desktop Header -->
                <div class="hidden md:grid grid-cols-12 gap-3 py-3 px-3 bg-gray-100 rounded-lg font-semibold text-sm text-gray-700 mb-3">
                    <div class="col-span-2">Name</div>
                    <div class="col-span-1">Wochentag</div>
                    <div class="col-span-1">Zeit</div>
                    <div class="col-span-1">Teilnehmer</div>
                    <div class="col-span-2">Gruppen</div>
                    <div class="col-span-2">Verantwortlich</div>
                    <div class="col-span-1">Zeitraum</div>
                    <div class="col-span-2">Aktionen</div>
                </div>

                @forelse($arbeitsgemeinschaften as $ag)
                    <div class="border border-gray-200 rounded-lg p-3 mb-3 hover:border-purple-500 hover:shadow-md transition-all duration-200 @if($loop->iteration % 2 == 0) bg-gray-50 @endif">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                            <!-- Name -->
                            <div class="md:col-span-2">
                                <span class="inline md:hidden font-semibold text-gray-700 mr-2">Name:</span>
                                <span class="font-semibold text-gray-800">{{ $ag->name }}</span>
                            </div>

                            <!-- Wochentag -->
                            <div class="md:col-span-1">
                                <span class="inline md:hidden font-semibold text-gray-700 mr-2">Wochentag:</span>
                                <span class="text-sm text-gray-700">{{$weekdays[$ag->weekday]}}</span>
                            </div>

                            <!-- Zeit -->
                            <div class="md:col-span-1">
                                <span class="inline md:hidden font-semibold text-gray-700 mr-2">Zeit:</span>
                                <span class="text-sm text-gray-700">{{ $ag->start_time->format('H:i') }} - {{ $ag->end_time->format('H:i') }}</span>
                            </div>

                            <!-- Teilnehmer -->
                            <div class="md:col-span-1">
                                <span class="inline md:hidden font-semibold text-gray-700 mr-2">Teilnehmer:</span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                    <i class="fas fa-users"></i>
                                    {{ $ag->participants->count() }} / {{ $ag->max_participants }}
                                </span>
                            </div>

                            <!-- Gruppen -->
                            <div class="md:col-span-2">
                                <span class="inline md:hidden font-semibold text-gray-700 mr-2">Gruppen:</span>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($ag->groups as $group)
                                        <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded">
                                            {{ $group->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Verantwortlich -->
                            <div class="md:col-span-2">
                                <span class="inline md:hidden font-semibold text-gray-700 mr-2">Verantwortlich:</span>
                                <span class="text-sm text-gray-700">{{ $ag->manager->name }}</span>
                            </div>

                            <!-- Zeitraum -->
                            <div class="md:col-span-1">
                                <span class="inline md:hidden font-semibold text-gray-700 mr-2">Zeitraum:</span>
                                <span class="text-xs text-gray-600">{{ $ag->start_date->format('d.m.Y') }} - {{ $ag->end_date->format('d.m.Y') }}</span>
                            </div>

                            <!-- Aktionen -->
                            <div class="md:col-span-2">
                                <span class="inline-block md:hidden font-semibold text-gray-700 mb-2">Aktionen:</span>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('verwaltung.arbeitsgemeinschaften.teilnehmer', $ag) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-medium rounded-lg transition-colors"
                                       data-bs-toggle="tooltip"
                                       title="Teilnehmer verwalten">
                                        <i class="fas fa-users"></i>
                                        <span class="hidden sm:inline">Teilnehmer</span>
                                    </a>

                                    <a href="{{ route('verwaltung.arbeitsgemeinschaften.export', $ag) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors"
                                       data-bs-toggle="tooltip"
                                       title="Teilnehmerliste exportieren">
                                        <i class="fas fa-download"></i>
                                        <span class="hidden sm:inline">Export</span>
                                    </a>

                                    <a href="{{ route('verwaltung.arbeitsgemeinschaften.edit', $ag) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors"
                                       data-bs-toggle="tooltip"
                                       title="Bearbeiten">
                                        <i class="fas fa-edit"></i>
                                        <span class="hidden sm:inline">Bearbeiten</span>
                                    </a>

                                    @if($ag->participants->isEmpty())
                                        <form action="{{ route('verwaltung.arbeitsgemeinschaften.destroy', $ag) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Sind Sie sicher, dass Sie diese Arbeitsgemeinschaft löschen möchten?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors"
                                                    data-bs-toggle="tooltip"
                                                    title="Löschen">
                                                <i class="fas fa-trash"></i>
                                                <span class="hidden sm:inline">Löschen</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex items-start gap-3 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                        <p class="text-blue-800 text-sm mb-0">Keine Arbeitsgemeinschaften verfügbar.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>


@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush

@push('css')
    <style>
        /* Zebra-Striping für die Zeilen */
        .card-body .row:nth-of-type(even) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Hover-Effekt */
        .card-body .row:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Optimierungen für mobile Ansicht */
        @media (max-width: 768px) {
            /* Mehr Abstand zwischen den Zeilen auf mobil */
            .card-body .row {
                margin-bottom: 1rem;
                padding: 0.5rem;
                border: 1px solid rgba(0, 0, 0, 0.1);
                border-radius: 0.25rem;
            }

            /* Labels auf mobil */
            .card-body .row [class*="col-"] {
                margin-bottom: 0.25rem;
            }

            /* Letzes Element ohne Margin */
            .card-body .row [class*="col-"]:last-child {
                margin-bottom: 0;
            }

            /* Aktionen-Buttons auf mobil */
            .btn-group {
                display: flex;
                gap: 0.5rem;
            }

            /* Formular für Löschen-Button auf mobil */
            .btn-group form {
                margin: 0;
            }
        }

        /* Button-Text auf kleinen Bildschirmen ausblenden */
        @media (max-width: 768px) {
            .button-text {
                display: none;
            }

            .bi {
                font-size: 1.2rem;
                margin: 0;
            }
        }
    </style>
@endpush
