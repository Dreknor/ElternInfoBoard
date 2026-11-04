@extends('layouts.app')
@section('title') - Schülerkonten-Import @endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header border-bottom">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h5 class="card-title mb-0"><i class="fas fa-file-import" style="color: var(--color-primary);"></i> Schülerkonten-Import</h5>
                <a href="{{ route('schuelerkonten.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Schülerkonten</a>
            </div>
        </div>
        <div class="card-body space-y-4">
            @if(! $result)
                <p class="text-sm">
                    Legt Schülerkonten für <strong>vorhandene Kinder</strong> an oder sperrt sie. Eine Zeile je Kind mit
                    <strong>Schüler-ID</strong> (wie im Schüler-Import), optional <strong>Benutzername</strong> (leer = vorname.nachname)
                    und <strong>Konto</strong> (<code>ja</code> = anlegen bzw. entsperren, <code>nein</code> = sperren).
                    Zugeordnet wird nur über die Schüler-ID, nie über den Namen. Die Vorschau ändert noch nichts.
                </p>
                <p class="text-sm" style="color: var(--color-text-secondary);">Nicht verwechseln mit dem „Schüler-Import mit Schüler-ID“: Der legt Kinder und Elternkonten an.</p>
                <form method="POST" action="{{ route('schuelerkonten.import.preview') }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label for="file" class="form-label text-sm">Datei (xlsx, ods oder csv)</label>
                        <input id="file" type="file" name="file" required accept=".xlsx,.xls,.ods,.csv" class="form-control">
                    </div>
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Vorschau</button>
                </form>
                <a href="{{ route('schuelerkonten.import', ['vorlage' => 1]) }}" class="text-sm" style="color: var(--color-primary);"><i class="fas fa-download"></i> Vorlage herunterladen</a>
            @else
                <div class="flex flex-wrap gap-2 text-sm">
                    @foreach($result['summary'] as $key => $count)
                        <span class="rounded-full border px-3 py-1 {{ $key === 'klaerung' ? 'border-amber-300 bg-amber-50 text-amber-900' : '' }}" style="border-color: var(--color-card-border);">
                            {{ $key === 'klaerung' ? 'Klärungsfälle' : ucfirst($key) }}: <strong>{{ $count }}</strong>
                        </span>
                    @endforeach
                </div>

                @if($done ?? false)
                    <div class="alert alert-success text-sm">
                        Import ausgeführt.
                        @if(! empty($resultId))
                            <a href="{{ route('schuelerkonten.import', ['ergebnis' => $resultId]) }}" class="btn btn-success btn-sm ml-2"><i class="fas fa-print"></i> Zugangszettel der neuen Konten (PDF)</a>
                            <div class="mt-1 text-xs">Die Codes entstehen beim Herunterladen. Ein erneutes Herunterladen erzeugt neue Codes.</div>
                        @endif
                    </div>
                @else
                    <form method="POST" action="{{ route('schuelerkonten.import.execute') }}" class="flex flex-wrap gap-2">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button class="btn btn-success"><i class="fas fa-check"></i> Import ausführen</button>
                        <a href="{{ route('schuelerkonten.import') }}" class="btn btn-outline-secondary">Abbrechen</a>
                    </form>
                @endif

                <div class="hidden md:block">
                    <table class="table w-full text-sm">
                        <thead><tr><th>Zeile</th><th>Schüler-ID</th><th>Kind</th><th>Klasse</th><th>Benutzername</th><th>Aktion</th><th>Hinweis</th></tr></thead>
                        <tbody>
                            @foreach($result['rows'] as $row)
                                <tr class="{{ $row['aktion'] === \App\Services\Students\StudentImportService::ACTION_SKIP ? 'bg-amber-50' : '' }}">
                                    <td>{{ $row['zeile'] }}</td><td>{{ $row['schueler_id'] }}</td><td>{{ $row['kind'] ?? '–' }}</td><td>{{ $row['klasse'] ?? '–' }}</td>
                                    <td class="font-mono">{{ $row['benutzername'] ?? '–' }}</td><td>{{ $row['aktion'] }}</td><td>{{ $row['hinweis'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="space-y-2 md:hidden">
                    @foreach($result['rows'] as $row)
                        <div class="rounded-lg border p-3 text-sm {{ $row['aktion'] === \App\Services\Students\StudentImportService::ACTION_SKIP ? 'border-amber-300 bg-amber-50' : '' }}" style="{{ $row['aktion'] === \App\Services\Students\StudentImportService::ACTION_SKIP ? '' : 'border-color: var(--color-card-border);' }}">
                            <div class="flex justify-between gap-2"><strong class="break-words">{{ $row['kind'] ?? $row['schueler_id'] }}</strong><span>{{ $row['aktion'] }}</span></div>
                            <div style="color: var(--color-text-secondary);">Zeile {{ $row['zeile'] }} · ID {{ $row['schueler_id'] }}@if($row['klasse']) · {{ $row['klasse'] }}@endif</div>
                            @if($row['benutzername'])<div class="break-all font-mono">{{ $row['benutzername'] }}</div>@endif
                            @if($row['hinweis'])<div class="mt-1">{{ $row['hinweis'] }}</div>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
