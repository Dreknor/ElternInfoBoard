@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class=" card shadow-lg">
            <div class="card-header  bg-light d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">unbestätigte Pflichtstunden</h3>
            </div>
            <div class="card-body">

            </div>
            <div class="card-body">
                <div class="table-responsive-md">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Stundenanzahl</th>
                            <th>Person</th>
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
                                    {{ $pflichtstunde->user->name }}
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
                                        <form class="form form-inline" id="approve-form-{{ $pflichtstunde->id }}" action="{{ route('pflichtstunden.approve', $pflichtstunde) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-success btn-sm mb-1" onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich bestätigen?');">
                                                <i class="fas fa-check-circle"></i> Bestätigen
                                            </button>
                                        </form>
                                        <form class="form form-inline ml-3" id="approve-form-{{ $pflichtstunde->id }}" action="{{ route('pflichtstunden.reject', $pflichtstunde) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <input name="rejection_reason" type="text" class="form-control form-control-sm mb-1" placeholder="Ablehnungsgrund" required>
                                            <button type="submit" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich ablehnen?');">
                                                <i class="fas fa-times-circle"></i> Ablehnen
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card shadow-lg mt-3">
            <div class="card-header  bg-light d-flex justify-content-between align-items-center">
                Übersicht der Pflichtstunden
            </div>
            <div class="card-body">
                <div class="table-responsive-md">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Stundenanzahl</th>
                            <th>Prozent erfüllt</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>
                                    @php
                                        $totalMinutes = $user->pflichtstunden->where('approved', true)->sum('duration');
                                    @endphp
                                    @if($totalMinutes > 60)
                                        {{ floor($totalMinutes / 60) }} Std. {{ $totalMinutes % 60 }} Min.
                                    @else
                                        {{ $totalMinutes }} Min.
                                    @endif
                                </td>
                                <td >
                                    @php
                                        $totalMinutes = $user->pflichtstunden->where('approved', true)->sum('duration');
                                        $requiredMinutes = $pflichtstunden_settings->pflichtstunden_anzahl * 60;
                                    @endphp

                                    @if($requiredMinutes > 0)
                                        {{ min(100, round(($totalMinutes / $requiredMinutes) * 100)) }}%
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
