@extends('layouts.app')

@section('title') - Familien prüfen @endsection

@section('content')
    <div class="container-fluid">
        <a href="{{ route('families.index') }}" class="btn btn-primary mb-2">Zurück</a>

        {{-- Meldungen von Eltern (E3) --}}
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="mb-0">Meldungen „Verbindung ist falsch“ ({{ $reports->count() }})</h4>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($reports as $report)
                    <li class="list-group-item">
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="mr-auto">
                                <strong>{{ $report->user?->name }}</strong> ↔
                                <a href="{{ route('child.edit', $report->child_id) }}#bezugspersonen">{{ $report->child?->first_name }} {{ $report->child?->last_name }}</a>
                                <br><small class="text-muted">gemeldet von {{ $report->reporter?->name ?? 'unbekannt' }} am {{ $report->created_at->format('d.m.Y') }}</small>
                                @if($report->note)
                                    <div class="small mt-1">„{{ $report->note }}“</div>
                                @endif
                            </div>
                            <form action="{{ route('guardians.destroy', [$report->child_id, $report->user_id]) }}" method="POST" class="mr-2"
                                  onsubmit="return confirm('Beziehung entfernen?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Beziehung entfernen</button>
                            </form>
                            <form action="{{ route('guardians.review', [$report->child_id, $report->user_id]) }}" method="POST" class="mr-2">
                                @csrf
                                <button class="btn btn-sm btn-outline-success">Beziehung ist korrekt</button>
                            </form>
                            <form action="{{ route('families.reports.resolve', $report) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">erledigt</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Keine offenen Meldungen.</li>
                @endforelse
            </ul>
        </div>

        {{-- Übernommene Beziehungen aus sorg2 (E3) --}}
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="mb-0">Übernommene Beziehungen prüfen ({{ $pending->count() }})</h4>
                <small class="text-muted">
                    Diese Verbindungen wurden aus der früheren Kontoverknüpfung übernommen. Hervorgehoben sind Kinder mit
                    mehr als zwei weiteren Bezugspersonen (Patchwork-Verdacht).
                </small>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($pending as $row)
                    @php($suspicious = $row['child']->parents->count() > 3)
                    <li @class(['list-group-item', 'list-group-item-warning' => $suspicious])>
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="mr-auto">
                                <strong>{{ $row['user']->name }}</strong> ↔
                                <a href="{{ route('child.edit', $row['child']) }}#bezugspersonen">{{ $row['child']->first_name }} {{ $row['child']->last_name }}</a>
                                <br><small class="text-muted">weitere Bezugspersonen: {{ $row['child']->parents->where('id', '!=', $row['user']->id)->pluck('name')->implode(', ') ?: '–' }}</small>
                            </div>
                            <form action="{{ route('guardians.review', [$row['child'], $row['user']]) }}" method="POST" class="mr-2">
                                @csrf
                                <button class="btn btn-sm btn-success">bestätigen</button>
                            </form>
                            <a href="{{ route('child.edit', $row['child']) }}#bezugspersonen" class="btn btn-sm btn-outline-primary mr-2">Rechte anpassen</a>
                            <form action="{{ route('guardians.destroy', [$row['child'], $row['user']]) }}" method="POST"
                                  onsubmit="return confirm('Beziehung entfernen?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">entfernen</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Keine ungeprüften übernommenen Beziehungen.</li>
                @endforelse
            </ul>
        </div>

        {{-- Klärungsfälle der Familienbildung (§7) --}}
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="mb-0">Klärungsfälle der Familienbildung ({{ $cases->count() }})</h4>
                <small class="text-muted">
                    Personen, die über gemeinsame Kinder verbunden sind, aber keine eindeutige Familie bilden
                    (z. B. Patchwork). Bitte Familien manuell festlegen – sie werden dadurch gesperrt.
                </small>
            </div>
            <div class="card-body">
                @forelse($cases as $index => $case)
                    <h6>Fall {{ $index + 1 }}</h6>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Person</th><th>Familie</th><th>Kinder</th></tr></thead>
                        <tbody>
                        @foreach($case['users'] as $caseUser)
                            <tr>
                                <td><a href="{{ url('users/'.$caseUser->id) }}">{{ $caseUser->name }}</a></td>
                                <td>
                                    @if($caseUser->family)
                                        <a href="{{ route('families.show', $caseUser->family) }}">{{ $caseUser->family->name }}</a>
                                    @else
                                        <span class="text-muted">keine</span>
                                    @endif
                                </td>
                                <td>
                                    {{ collect($case['childSets'][$caseUser->id] ?? [])->map(fn ($id) => $case['children']->get($id)?->first_name.' '.$case['children']->get($id)?->last_name)->implode(', ') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <form action="{{ route('families.store') }}" method="POST" class="form-inline mb-4">
                        @csrf
                        @foreach($case['users'] as $caseUser)
                            <label class="mr-2"><input type="checkbox" name="user_ids[]" value="{{ $caseUser->id }}" class="mr-1">{{ $caseUser->name }}</label>
                        @endforeach
                        <input type="text" name="name" class="form-control form-control-sm mr-2" placeholder="Name der Familie">
                        <button class="btn btn-sm btn-primary">Ausgewählte als Familie festlegen</button>
                    </form>
                @empty
                    <p class="text-muted mb-0">Keine Klärungsfälle.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
