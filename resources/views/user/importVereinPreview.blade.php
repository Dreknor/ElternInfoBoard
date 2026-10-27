@extends('layouts.app')
@section('title') - Vereinsmitglieder importieren @endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Vereins-Import – {{ $report->dryRun ? 'Vorschau (noch nichts gespeichert)' : 'Ergebnis' }}
            </h6>
        </div>
        <div class="card-body">
            @unless($report->dryRun)
                <div class="alert alert-success">Der Import wurde ausgeführt.</div>
            @endunless

            <p class="text-muted small">
                Geändert werden ausschließlich die Gruppe und die Rolle „Vereinsmitglied“. Andere Gruppen, Rollen und Kontodaten bleiben unverändert.
            </p>

            <table class="table table-sm w-auto">
                <tbody>
                @foreach($report->summary() as $label => $value)
                    <tr>
                        <th class="pr-4">{{ $label }}</th>
                        <td @class(['text-warning font-weight-bold' => in_array($label, ['Klärungsfälle', 'Aus dem Verein entfernt']) && $value > 0])>{{ $value }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($report->review !== [])
                <h6 class="mt-4 text-warning">Klärungsfälle (keine automatische Änderung)</h6>
                <ul>
                    @foreach($report->review as $case)
                        <li>Zeile {{ $case['row'] }}: {{ $case['message'] }}</li>
                    @endforeach
                </ul>
            @endif

            @foreach([
                'linked' => $report->dryRun ? 'Bestehende Konten, die Vereinsmitglied werden' : 'Bestehende Konten, die Vereinsmitglied wurden',
                'completed' => 'Mitglieder, bei denen Gruppe oder Rolle ergänzt '.($report->dryRun ? 'wird' : 'wurde'),
                'created' => $report->dryRun ? 'Neu anzulegende Konten' : 'Neu angelegte Konten',
                'removed' => $report->dryRun ? 'Würden aus Gruppe und Rolle „Vereinsmitglied“ entfernt' : 'Aus Gruppe und Rolle „Vereinsmitglied“ entfernt',
            ] as $list => $heading)
                @if($report->{$list} !== [])
                    <h6 class="mt-4">{{ $heading }} ({{ count($report->{$list}) }})</h6>
                    <ul class="small">
                        @foreach($report->{$list} as $entry)
                            <li>{{ $entry }}</li>
                        @endforeach
                    </ul>
                @endif
            @endforeach

            <div class="mt-4">
                @if($report->dryRun && $token)
                    <form action="{{ route('users.import.verein.confirm') }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Import jetzt ausführen?')">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="abgleich" value="{{ $options['removeMissing'] ? 1 : 0 }}">
                        <input type="hidden" name="send_email" value="{{ $options['sendEmail'] ? 1 : 0 }}">
                        <button class="btn btn-success">
                            <i class="fas fa-check mr-1"></i> Import bestätigen
                        </button>
                    </form>
                @endif
                <a href="{{ url('users/importVerein') }}" class="btn btn-outline-secondary">Zurück zum Import</a>
                <a href="{{ url('users/vereinsmitglieder/non-members') }}" class="btn btn-outline-success">Nicht-Vereinsmitglieder</a>
            </div>
        </div>
    </div>
@endsection
