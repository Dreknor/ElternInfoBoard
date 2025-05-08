@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary">
            <h3>Arbeitsgemeinschaften</h3>
        </div>

        <div class="card-body">
            <div class="row row-cols-1 row-cols-md-2 g-4">
                @forelse($arbeitsgemeinschaften as $ag)
                    <div class="col">
                        <div class="card h-100 bg-light border">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title">{{ $ag->name }}</h5>
                                        <div class="card-text">
                                            <p class="mb-1">
                                                <i class="bi bi-card-text"></i>
                                                {{$ag->description}}
                                            </p>
                                            <p class="mb-1">
                                                <i class="bi bi-calendar-date"></i>
                                                {{ $weekdays[$ag->weekday] }}
                                            </p>
                                            <p class="mb-1">
                                                <i class="bi bi-clock"></i>
                                                {{ $ag->start_time->format('H:i') }} - {{ $ag->end_time->format('H:i') }}
                                            </p>
                                            <p class="mb-1">
                                                <i class="bi bi-person"></i>
                                                Leitung: {{ $ag->manager->name }}
                                            </p>
                                            <p class="mb-1">
                                                <i class="bi bi-people"></i>
                                                {{ $ag->participants->count() }}/{{ $ag->max_participants }} Teilnehmer
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">


                                        @php
                                            $angemeldeteKinder = $ag->participants->filter(function($participant) {
                                                return auth()->user()->children()->contains($participant->id);
                                            });
                                        @endphp

                                        @if($angemeldeteKinder->isNotEmpty())
                                            <div class=" mt-2">
                                                <p class="mb-1"><strong>Angemeldete Kinder:</strong></p>
                                                <ul class="list-unstyled mb-0">
                                                    @foreach($angemeldeteKinder as $kind)
                                                        <li>
                                                            <i class="bi bi-check-circle-fill text-success"></i>
                                                            {{ $kind->first_name }} {{ $kind->last_name }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <p class="mt-2 text-muted">
                                                    Abmeldungen können nur über die Schule vorgenommen werden.
                                                </p>
                                            </div>

                                        @else
                                            <div class="alert alert-warning mt-2">
                                                <p class="mb-1"><strong>Keine eigenen Kinder angemeldet.</strong></p>
                                            </div>
                                        @endif

                                    </div>
                                </div>

                            </div>
                            <div class="card-footer">
                                @if($ag->participants->count() >= $ag->max_participants)
                                    <span class="badge bg-danger">Ausgebucht</span>
                                @elseif($availableChildrenByAg[$ag->id]->isNotEmpty())
                                    <form action="{{ route('arbeitsgemeinschaften.anmelden', $ag) }}" method="POST">
                                        @csrf
                                        <div class="input-group">
                                            <select name="child_id" class="custom-select form-select-sm">
                                                @foreach($availableChildrenByAg[$ag->id] as $child)
                                                    <option value="{{ $child->id }}">
                                                        {{ $child->last_name }}, {{ $child->first_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-person-plus"></i>
                                                Anmelden
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">Keine Anmeldung möglich</span>
                                @endif

                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">
                            Keine Arbeitsgemeinschaften gefunden.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
