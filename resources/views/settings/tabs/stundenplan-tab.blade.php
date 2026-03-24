<div class="tab-pane" id="stundenplan" role="tabpanel" aria-labelledby="stundenplan-tab">
    <form action="{{url('settings/stundenplan')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')

        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>Stundenplan Import-Einstellungen</strong><br>
            Hier können Sie die Import-Einstellungen für den Stundenplan verwalten. Der Import kann über die Web-UI oder eine gesicherte API-Schnittstelle erfolgen.
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="show_absent_teachers" value="1"
                           {{ $stundenplanSettings->show_absent_teachers ? 'checked' : '' }}>
                    Abwesende Lehrer anzeigen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn aktiviert, werden abwesende Lehrer im Stundenplan angezeigt. Wenn deaktiviert, werden die Namen abwesender Lehrer ausgeblendet.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="allow_web_import" value="1"
                           {{ $stundenplanSettings->allow_web_import ? 'checked' : '' }}>
                    Web-Import aktivieren
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Erlaubt den Import von Stundenplan-Daten über die Web-Oberfläche
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="allow_api_import" value="1"
                           {{ $stundenplanSettings->allow_api_import ? 'checked' : '' }}>
                    API-Import aktivieren
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Erlaubt den Import von Stundenplan-Daten über die API-Schnittstelle
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    API Import-URL
                    <input type="text" class="form-control" readonly
                           value="{{ $stundenplanSettings->import_api_url }}"
                           onclick="this.select()">
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Diese URL wird für den API-Import verwendet. Die URL ist schreibgeschützt.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    API-Key
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace"
                               id="api-key-display" readonly
                               value="{{ $stundenplanSettings->import_api_key }}"
                               onclick="this.select()">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="copyToClipboard('api-key-display')"
                                    title="API-Key kopieren">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </label>
                <button type="button" class="btn btn-warning btn-sm mt-2"
                        onclick="document.getElementById('regenerate-key-form').submit()">
                    <i class="fas fa-sync-alt"></i> Neuen API-Key generieren
                </button>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    <strong>Wichtig:</strong> Der API-Key kann auf mehrere Arten übergeben werden:
                    <br><br>
                    <strong>Methode 1: Query-Parameter (empfohlen, am einfachsten)</strong>
                    <pre class="bg-light p-2 small mt-2" style="font-size: 10px;">
POST {{ url('/api/stundenplan/import') }}?key={{ substr($stundenplanSettings->import_api_key, 0, 20) }}...
Content-Type: application/json

{
  "Basisdaten": { ... },
  "Zeitslots": [ ... ],
  "Klassen": [ ... ]
}</pre>

                    <strong>Mit optionalen Parametern (schulform, beschreibung):</strong>
                    <pre class="bg-light p-2 small mt-2" style="font-size: 10px;">
POST {{ url('/api/stundenplan/import') }}?key=...&schulform=Oberschule&beschreibung=Klassen%205-10
Content-Type: application/json

{
  "Gesamtexport": { ... }
}

# Oder im JSON-Body:
{
  "schulform": "Oberschule",
  "beschreibung": "Klassen 5-10",
  "Gesamtexport": { ... }
}

# Hinweis: URL-Parameter haben Vorrang vor JSON-Body</pre>

                    <strong>Methode 2: Header</strong>
                    <pre class="bg-light p-2 small mt-2" style="font-size: 10px;">
POST {{ url('/api/stundenplan/import') }}
X-API-Key: {{ substr($stundenplanSettings->import_api_key, 0, 20) }}...
Content-Type: application/json

{
  "Basisdaten": { ... },
  ...
}</pre>
                    <strong>Methode 3: Bearer Token</strong>
                    <pre class="bg-light p-2 small mt-2" style="font-size: 10px;">
POST {{ url('/api/stundenplan/import') }}
Authorization: Bearer {{ substr($stundenplanSettings->import_api_key, 0, 20) }}...
Content-Type: application/json

{
  "Basisdaten": { ... },
  ...
}</pre>

                    <div class="alert alert-info mt-3 p-2" style="font-size: 11px;">
                        <strong><i class="fas fa-lightbulb"></i> Tipp für Automation:</strong><br>
                        Verwenden Sie URL-Parameter für Schulform, um Original-Export-Dateien direkt zu importieren:<br>
                        <code class="d-inline">?key=...&schulform=Grundschule&beschreibung=Import%20vom%2012.02.2026</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row mt-3 p-2">
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Einstellungen speichern
                </button>
            </div>
        </div>
    </form>

    <!-- Regenerate API Key Form (separate from main form) -->
    <form id="regenerate-key-form" action="{{ url('settings/stundenplan/regenerate-key') }}" method="POST" style="display: none;"
          onsubmit="return confirm('Sind Sie sicher, dass Sie einen neuen API-Key generieren möchten? Der alte Key wird ungültig!')">
        @csrf
    </form>

    <!-- Import Status -->
    <div class="mt-4 p-3 border-top">
        <h6>Import-Status</h6>
        <div id="import-status-container">
            <button type="button" class="btn btn-info btn-sm" onclick="loadImportStatus()">
                <i class="fas fa-sync"></i> Status laden
            </button>
        </div>
    </div>
</div>

@push('js')
<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');

    // Visual feedback
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => {
        btn.innerHTML = originalHTML;
    }, 2000);
}

function loadImportStatus() {
    const container = document.getElementById('import-status-container');
    container.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Lade Status...';

    fetch('{{ url('api/stundenplan/status') }}')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.imported) {
                container.innerHTML = `
                    <div class="alert alert-success">
                        <strong><i class="fas fa-check-circle"></i> Stundenplan importiert</strong><br>
                        <small>
                            <strong>Zeitraum:</strong> ${data.data.basisdaten.DatumVon} - ${data.data.basisdaten.DatumBis}<br>
                            <strong>Klassen:</strong> ${data.data.klassen_count}<br>
                            <strong>Zeitslots:</strong> ${data.data.zeitslots_count}<br>
                            <strong>Zuletzt geändert:</strong> ${data.data.last_modified}
                        </small>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> ${data.message || 'Noch kein Stundenplan importiert'}
                    </div>
                `;
            }
        })
        .catch(error => {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Fehler beim Laden des Status
                </div>
            `;
        });
}
</script>
@endpush



