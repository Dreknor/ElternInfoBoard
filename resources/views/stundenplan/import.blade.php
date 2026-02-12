@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-upload"></i> Stundenplan importieren
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Import-Hinweise:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Laden Sie die JSON-Datei aus dem Indiware Unterrichtsplaner hoch</li>
                                        <li>Maximale Dateigröße: 10 MB</li>
                                        <li>Die Datei muss gültige JSON-Struktur mit Basisdaten, Zeitslots und Klassen enthalten</li>
                                        <li>Nach dem Import werden die Daten sofort auf allen Stundenplan-Seiten sichtbar</li>
                                    </ul>
                                </div>

                                <form action="{{ url('stundenplan/import') }}" method="post" enctype="multipart/form-data">
                                    @csrf

                                    <div class="form-group">
                                        <label for="json_file">JSON-Datei auswählen</label>
                                        <input type="file"
                                               class="form-control-file @error('json_file') is-invalid @enderror"
                                               id="json_file"
                                               name="json_file"
                                               accept=".json"
                                               required>
                                        @error('json_file')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="schulform">Schulform <small class="text-muted">(optional)</small></label>
                                        <input type="text"
                                               class="form-control @error('schulform') is-invalid @enderror"
                                               id="schulform"
                                               name="schulform"
                                               placeholder="z.B. Grundschule, Oberschule, Gymnasium"
                                               maxlength="100">
                                        <small class="form-text text-muted">
                                            Ermöglicht parallele Stundenpläne mit unterschiedlichen Zeitrastern (z.B. Grundschule und Oberschule).
                                        </small>
                                        @error('schulform')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="beschreibung">Beschreibung <small class="text-muted">(optional)</small></label>
                                        <textarea class="form-control @error('beschreibung') is-invalid @enderror"
                                                  id="beschreibung"
                                                  name="beschreibung"
                                                  rows="2"
                                                  maxlength="500"
                                                  placeholder="Zusätzliche Informationen zu diesem Stundenplan..."></textarea>
                                        @error('beschreibung')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Hochladen und importieren
                                    </button>
                                    <a href="{{ url('stundenplan') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Zurück zum Stundenplan
                                    </a>
                                </form>
                            </div>

                            <div class="col-md-6">
                                @if($currentData)
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <i class="fas fa-check-circle"></i> Aktuell importierter Stundenplan
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Zeitraum:</th>
                                                    <td>{{ $currentData['Basisdaten']['DatumVon'] ?? 'N/A' }} - {{ $currentData['Basisdaten']['DatumBis'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Schulwochen:</th>
                                                    <td>{{ $currentData['Basisdaten']['SwVon'] ?? 'N/A' }} - {{ $currentData['Basisdaten']['SwBis'] ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Anzahl Klassen:</th>
                                                    <td>{{ count($currentData['Klassen'] ?? []) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Anzahl Zeitslots:</th>
                                                    <td>{{ count($currentData['Zeitslots'] ?? []) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Import-Zeitstempel:</th>
                                                    <td>{{ $currentData['Basisdaten']['Zeitstempel'] ?? 'N/A' }}</td>
                                                </tr>
                                            </table>

                                            <div class="mt-3">
                                                <strong>Klassen:</strong>
                                                <div class="d-flex flex-wrap mt-2">
                                                    @foreach($currentData['Klassen'] ?? [] as $klasse)
                                                        <span class="badge badge-primary mr-1 mb-1">{{ $klasse['Kurzform'] }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Noch kein Stundenplan importiert</strong><br>
                                        Es wurde noch keine Stundenplan-Datei hochgeladen. Nach dem ersten Import werden hier Informationen zum aktuellen Stundenplan angezeigt.
                                    </div>
                                @endif

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-key"></i> API-Import
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="small mb-2">
                                            <strong>Vollständige Import-URL (empfohlen):</strong>
                                        </p>
                                        <code class="d-block bg-light p-2 small" style="word-break: break-all;">
                                            {{ url('/api/stundenplan/import') }}?key={{ $stundenplanSettings->import_api_key }}
                                        </code>

                                        <p class="small mt-3 mb-2">
                                            <strong>Verwendung:</strong>
                                        </p>
                                        <pre class="bg-light p-2 small">POST zur obigen URL mit JSON-Body:
{
  "Basisdaten": { ... },
  "Zeitslots": [ ... ],
  "Klassen": [ ... ]
}</pre>

                                        <p class="small mb-0">
                                            <strong>Status:</strong>
                                            @if($stundenplanSettings->allow_api_import)
                                                <span class="badge badge-success">Aktiviert</span>
                                            @else
                                                <span class="badge badge-danger">Deaktiviert</span>
                                            @endif
                                        </p>
                                        <p class="small mb-0 mt-2">
                                            Die API-Einstellungen können in den
                                            <a href="{{ url('settings') }}#stundenplan-tab" target="_blank">System-Einstellungen</a>
                                            verwaltet werden.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    // Preview file name when selected
    document.getElementById('json_file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            console.log('Selected file:', fileName);
        }
    });
</script>
@endpush



