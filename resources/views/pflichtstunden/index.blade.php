@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class=" card shadow-lg">
            <div class="card-header  bg-light d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Pflichtstunden</h3>
            </div>
            <div class="card-body">
                {!! $pflichtstunden_settings->pflichtstunden_text !!}
            </div>
            <div class="card-body">
                <div class="table-responsive-md">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Stundenanzahl</th>
                            <th>Beschreibung</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($pflichtstunden as $pflichtstunde)
                            <tr>
                                <td>
                                    @if($pflichtstunde->start->isSameDay($pflichtstunde->end))
                                        {{ $pflichtstunde->start->format('d.m.Y') }} von {{ $pflichtstunde->start->format('H:i') }} bis {{ $pflichtstunde->end->format('H:i') }}
                                    @else
                                        {{ $pflichtstunde->start->format('d.m.Y H:i') }} bis {{ $pflichtstunde->end->format('d.m.Y H:i') }}
                                    @endif
                                </td>
                                <td>
                                    @if($pflichtstunde->duration > 60)
                                        {{ floor($pflichtstunde->duration / 60) }} Std. {{ $pflichtstunde->duration % 60 }} Min.
                                    @else
                                        {{ $pflichtstunde->duration }} Min.
                                    @endif
                                </td>
                                <td>
                                    {{ $pflichtstunde->description }}
                                </td>
                                <td>
                                    @if($pflichtstunde->approved)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i>
                                            bestätigt
                                        </span>
                                    @elseif($pflichtstunde->rejected)
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle"></i>
                                            abgelehnt: {{$pflichtstunde->rejection_reason}}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-hourglass-half"></i> In Bearbeitung
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Gesamtstunden:</th>
                            <th>
                                @if($pflichtstunden->sum('duration') > 60)
                                    {{ floor($pflichtstunden->sum('duration') / 60) }} Std. {{ $pflichtstunden->sum('duration') % 60 }} Min.
                                @else
                                    {{ $pflichtstunden->sum('duration') }} Min.
                                @endif
                            </th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Verbleibende Stunden:</th>
                            <th>
                                @php
                                    $remaining = $pflichtstunden_settings->pflichtstunden_anzahl *60 - $pflichtstunden->where('approved', true)->sum('duration');
                                @endphp
                                @if($remaining > 60)
                                    {{ floor($remaining / 60) }} Std. {{ $remaining % 60 }} Min.
                                @elseif($remaining > 0)
                                    {{ $remaining }} Min.
                                @else
                                    0 Min.
                                @endif
                            </th>
                        </tr>
                        <tr>
                            <th colspan="3">
                                Offener Betrag ({{$pflichtstunden_settings->pflichtstunden_betrag}} € je Pflichtstunde):
                            </th>
                            <th>
                               @php
                                   $remaining_hours = ($pflichtstunden_settings->pflichtstunden_anzahl * 60 - $pflichtstunden->where('approved', true)->sum('duration')) / 60;
                                   $betrag_gesamt = $pflichtstunden_settings->pflichtstunden_anzahl * $pflichtstunden_settings->pflichtstunden_betrag;
                                   $offener_betrag = $remaining_hours * $pflichtstunden_settings->pflichtstunden_betrag;

                                @endphp
                                {{number_format($offener_betrag, 2)}} € von {{$betrag_gesamt}} €
                            </th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class ="card mt-3 shadow-lg">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Pflichtstunden eintragen</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('pflichtstunden.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="start" class="form-label">Startdatum und -uhrzeit</label>
                        <input type="datetime-local" class="form-control @error('start') is-invalid @enderror" id="start" name="start" value="{{ old('start') }}" required>
                        @error('start')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="end" class="form-label">Enddatum und -uhrzeit</label>
                        <input type="datetime-local" class="form-control @error('end') is-invalid @enderror" id="end" name="end" value="{{ old('end') }}" required>
                        @error('end')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Beschreibung</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Pflichtstunden eintragen</button>
                </form>
            </div>
        </div>
    </div>
@endsection
