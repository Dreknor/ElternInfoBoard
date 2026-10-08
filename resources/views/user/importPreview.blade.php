@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Schüler-Import – {{ $report->dryRun ? 'Vorschau (noch nichts gespeichert)' : 'Ergebnis' }}
            </h6>
        </div>
        <div class="card-body">
            @unless($report->dryRun)
                <div class="alert alert-success">Der Import wurde ausgeführt.</div>
            @endunless

            <table class="table table-sm w-auto">
                <tbody>
                @foreach($report->summary() as $label => $value)
                    <tr>
                        <th class="pr-4">{{ $label }}</th>
                        <td @class(['text-danger font-weight-bold' => $label === 'Fehler' && $value > 0, 'text-warning font-weight-bold' => in_array($label, ['Klärungsfälle', 'Abgänger']) && $value > 0])>{{ $value }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($report->errors !== [])
                <h6 class="mt-4 text-danger">Fehler (Zeilen wurden nicht oder nur teilweise übernommen)</h6>
                <ul>
                    @foreach($report->errors as $error)
                        <li>Zeile {{ $error['row'] }}: {{ $error['message'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if($report->review !== [])
                <h6 class="mt-4 text-warning">Klärungsfälle (keine automatische Änderung)</h6>
                <ul>
                    @foreach($report->review as $case)
                        <li>Zeile {{ $case['row'] }}: {{ $case['message'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if($report->leavers !== [])
                <h6 class="mt-4">{{ $report->dryRun ? 'Würden als Abgänger markiert' : 'Als Abgänger markiert' }} ({{ count($report->leavers) }})</h6>
                <ul class="small">
                    @foreach($report->leavers as $leaver)
                        <li>{{ $leaver }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4">
                @if($report->dryRun && $token)
                    <form action="{{ route('users.import.schueler.confirm') }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Import jetzt ausführen?')">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="abgaenger" value="{{ $abgaenger ? 1 : 0 }}">
                        <button class="btn btn-success">
                            <i class="fas fa-check mr-1"></i> Import bestätigen
                        </button>
                    </form>
                @endif
                <a href="{{ url('users/import') }}" class="btn btn-outline-secondary">Zurück zum Import</a>
                @can('manage families')
                    <a href="{{ route('families.review') }}" class="btn btn-outline-warning">Familien prüfen</a>
                @endcan
            </div>
        </div>
    </div>
@endsection
