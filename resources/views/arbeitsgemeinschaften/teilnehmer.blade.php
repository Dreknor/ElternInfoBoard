@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Teilnehmerverwaltung: {{ $arbeitsgemeinschaft->name }}</h2>
                <a href="{{ route('verwaltung.arbeitsgemeinschaften.index') }}"
                   class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span class="button-text">Zurück</span>
                </a>
            </div>
            <div class="card-body">
                <!-- Info-Box -->
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Maximale Teilnehmer:</strong> {{ $arbeitsgemeinschaft->max_participants }}
                        </div>
                        <div class="col-md-4">
                            <strong>Aktuelle Teilnehmer:</strong> {{ $participants->count() }}
                        </div>
                        <div class="col-md-4">
                            <strong>Freie Plätze:</strong>
                            {{ $arbeitsgemeinschaft->max_participants - $participants->count() }}
                        </div>
                    </div>
                </div>

                <!-- Teilnehmer hinzufügen -->
                @if($arbeitsgemeinschaft->participants()->count() < $arbeitsgemeinschaft->max_participants)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Teilnehmer hinzufügen</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('verwaltung.arbeitsgemeinschaften.teilnehmer.add', $arbeitsgemeinschaft) }}"
                                  method="POST"
                                  class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-10">
                                    <label for="child_id" class="form-label">Kind auswählen</label>
                                    <select name="child_id" id="child_id" class="custom-select" required>
                                        <option value="">Bitte wählen...</option>
                                        @foreach($availableChildren as $child)
                                            <option value="{{ $child->id }}">
                                                {{ $child->last_name }}, {{ $child->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-plus-circle"></i>
                                        <span class="button-text">Hinzufügen</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Teilnehmerliste -->
                <div class="card">
                    <div class="card-header">
                        <h3>Aktuelle Teilnehmer</h3>
                    </div>
                    <div class="card-body">
                        @if($participants->isEmpty())
                            <div class="alert alert-info">
                                Noch keine Teilnehmer vorhanden.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Gruppen</th>
                                        <th>Hinzugefügt von</th>
                                        <th>Aktionen</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($participants as $participant)
                                        <tr>
                                            <td>{{ $participant->last_name }}, {{$participant->first_name}}</td>
                                            <td>{{ $participant->group->name }} @if($participant->class->id != $participant->group->id) , {{$participant->class->name}} @endif</td>
                                            <td>{{ $participant->pivot->user->name ?? 'Unbekannt' }}</td>
                                            <td>
                                                <form action="{{ route('verwaltung.arbeitsgemeinschaften.teilnehmer.remove', [$arbeitsgemeinschaft, $participant]) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Soll dieser Teilnehmer wirklich entfernt werden?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                        <span class="button-text">Entfernen</span>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            @media (max-width: 768px) {
                .button-text {
                    display: none;
                }

                .bi {
                    font-size: 1.2rem;
                }

                /* Tabellen-Anpassung für mobile Ansicht */
                .table-responsive table {
                    display: block;
                }

                .table-responsive tr {
                    display: block;
                    margin-bottom: 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 0.25rem;
                }

                .table-responsive td {
                    display: block;
                    position: relative;
                    padding-left: 50%;
                    text-align: left;
                    border: none;
                    border-bottom: 1px solid #dee2e6;
                }

                .table-responsive td:before {
                    content: attr(data-label);
                    position: absolute;
                    left: 0.75rem;
                    width: 45%;
                    font-weight: bold;
                }

                .table-responsive td:last-child {
                    border-bottom: none;
                }

                .table-responsive thead {
                    display: none;
                }
            }
        </style>
    @endpush
@endsection
