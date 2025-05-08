@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Aktive Arbeitsgemeinschaften</h4>
                        <a href="{{ route('verwaltung.arbeitsgemeinschaften.create') }}"
                           class="btn btn-primary"
                           data-bs-toggle="tooltip"
                           data-bs-placement="top"
                           title="Neue AG erstellen">
                            <span class="button-text">Neue AG erstellen</span>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="d-none d-md-flex row fw-bold py-2 border-bottom">
                            <div class="col-md-3 font-weight-bold">Name</div>
                            <div class="col-md-1 font-weight-bold">Wochentag</div>
                            <div class="col-md-1 font-weight-bold">Zeit</div>
                            <div class="col-md-1 font-weight-bold">Teilnehmer</div>
                            <div class="col-md-2 font-weight-bold">Gruppen</div>
                            <div class="col-md-2 font-weight-bold">Verantwortlich</div>
                            <div class="col-md-2 font-weight-bold">Aktionen</div>
                        </div>

                        @forelse($arbeitsgemeinschaften as $ag)
                            <div class="row py-2 border-bottom align-items-center">
                                <div class="col-md-3 py-1">
                                    <span class="d-inline d-md-none fw-bold">Name: </span>
                                    {{ $ag->name }}
                                </div>

                                <div class="col-md-1 py-1">
                                    <span class="d-inline d-md-none fw-bold">Wochentag: </span>
                                    {{$weekdays[$ag->weekday]}}
                                </div>

                                <div class="col-md-1 py-1">
                                    <span class="d-inline d-md-none fw-bold">Zeit: </span>
                                    {{ $ag->start_time->format('H:i') }} - {{ $ag->end_time->format('H:i') }}
                                </div>

                                <div class="col-md-1 py-1">
                                    <span class="d-inline d-md-none fw-bold">Teilnehmer: </span>
                                    {{ $ag->participants->count() }} / {{ $ag->max_participants }}
                                </div>

                                <div class="col-md-2 py-1">
                                    @foreach($ag->groups as $group)
                                        <span class="badge bg-primary p-2">
                                            {{ $group->name }}
                                        </span>
                                    @endforeach
                                </div>

                                <div class="col-md-2 py-1">
                                    <span class="d-inline d-md-none fw-bold">Verantwortlich: </span>
                                    {{ $ag->manager->name }}
                                </div>

                                <div class="col-md-2 py-1">
                                    <span class="d-inline d-md-none fw-bold">Aktionen: </span>
                                   <div class="row">
                                       <div class="col">
                                           <a href="{{ route('verwaltung.arbeitsgemeinschaften.teilnehmer', $ag) }}"
                                              class="btn btn-info btn-sm"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              title="Teilnehmer verwalten">
                                               <i class="bi bi-people"></i>
                                               <span class="button-text">Teilnehmer</span>
                                           </a>

                                       </div>
                                       <div class="col">
                                           <a href="{{ route('verwaltung.arbeitsgemeinschaften.export', $ag) }}"
                                              class="btn btn-success btn-sm"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              title="Teilnehmerliste exportieren">
                                               <i class="bi bi-download"></i>
                                               <span class="button-text">Exportieren</span>
                                           </a>
                                       </div>

                                       <div class="col">
                                           <a href="{{ route('verwaltung.arbeitsgemeinschaften.edit', $ag) }}"
                                              class="btn btn-sm btn-primary"
                                              data-bs-toggle="tooltip"
                                              data-bs-placement="top"
                                              title="Bearbeiten">
                                               <i class="fa fa-edit"></i>
                                               <span class="button-text">Bearbeiten</span>
                                           </a>
                                       </div>
                                       <div class="col">
                                           @if($ag->participants->isEmpty())
                                               <form action="{{ route('verwaltung.arbeitsgemeinschaften.destroy', $ag) }}"
                                                     method="POST"
                                                     class="d-inline"
                                                     onsubmit="return confirm('Sind Sie sicher, dass Sie diese Arbeitsgemeinschaft löschen möchten?');">
                                                   @csrf
                                                   @method('DELETE')
                                                   <button type="submit"
                                                           class="btn btn-sm btn-danger"
                                                           data-bs-toggle="tooltip"
                                                           data-bs-placement="top"
                                                           title="Löschen">
                                                       <i class="fa fa-trash"></i>
                                                       <span class="button-text">Löschen</span>
                                                   </button>
                                               </form>
                                           @endif
                                       </div>


                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="row">
                                <div class="col-12 text-center py-4">
                                    Keine Arbeitsgemeinschaften verfügbar.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
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
