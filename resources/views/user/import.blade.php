@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Benutzer importieren
            </h6>
        </div>

        <div class="card-body" x-data="{ importTyp: 'eltern' }">

            {{-- Flash-Meldungen --}}
            @if(session('Meldung'))
                <div class="alert alert-{{ session('type', 'info') }} alert-dismissible fade show" role="alert">
                    {{ session('Meldung') }}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            @endif

            {{-- Dynamischer Hinweisblock je Typ --}}
            <div x-show="importTyp === 'eltern'" class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Achtung Eltern-Import:</strong>
                Alle nicht-geschützten Gruppen werden vor dem Import <strong>geleert</strong> (Zuordnungen werden entfernt).
                Neue Benutzer erhalten ein zufälliges Passwort per E-Mail.
                Bitte prüfe, ob die Spaltennummern zur Excel-Überschrift passen.
            </div>
            <div x-show="importTyp === 'aufnahme'" class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Aufnahme-Import:</strong>
                Neue Benutzer werden angelegt und erhalten ein zufälliges Passwort per E-Mail.
                Bereits vorhandene Konten (gleiche E-Mail) werden nicht verändert.
            </div>
            <div x-show="importTyp === 'mitarbeiter'" class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Mitarbeiter-Import:</strong>
                Benötigte Spalten: <code>e_mail</code>, <code>vorname</code>, <code>nachname</code>.
                Die Rolle <em>Mitarbeiter</em> muss angelegt sein.
                Neue Benutzer erhalten ein zufälliges Passwort per E-Mail.
            </div>

            <form action="{{ url('/users/import') }}" method="post" class="form form-horizontal mt-3" enctype="multipart/form-data">
                @csrf

                {{-- Import-Typ zuerst wählen --}}
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Import-Typ</label>
                            <select class="custom-select" name="type" x-model="importTyp">
                                <option value="eltern">Eltern-Import</option>
                                <option value="aufnahme">Aufnahme-Import</option>
                                <option value="mitarbeiter">Mitarbeiter-Import</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Spaltenangaben (nur bei Eltern/Aufnahme relevant) --}}
                <div x-show="importTyp !== 'mitarbeiter'">
                    <h6 class="font-weight-bold text-muted mb-2">Spaltenzuordnung (1-basiert)</h6>
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>Klassenstufe</label>
                                <input type="number" name="klassenstufe" step="1" value="2" min="1" class="form-control border-input">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>Klassen&shy;gruppe</label>
                                <input type="number" name="lerngruppe" step="1" value="3" min="1" class="form-control border-input">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>Gruppenliste</label>
                                <input type="number" name="gruppen" step="1" value="1" min="1" class="form-control border-input">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>S1: Vorname</label>
                                <input type="number" name="S1Vorname" step="1" value="4" min="1" class="form-control border-input">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>S1: Nachname</label>
                                <input type="number" name="S1Nachname" step="1" value="5" min="1" class="form-control border-input">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>S1: E-Mail</label>
                                <input type="number" name="S1Email" step="1" value="6" min="1" class="form-control border-input">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>S2: Vorname</label>
                                <input type="number" name="S2Vorname" step="1" value="7" min="1" class="form-control border-input">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>S2: Nachname</label>
                                <input type="number" name="S2Nachname" step="1" value="8" min="1" class="form-control border-input">
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4">
                            <div class="form-group">
                                <label>S2: E-Mail</label>
                                <input type="number" name="S2Email" step="1" value="9" min="1" class="form-control border-input">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Datei-Upload --}}
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Excel-Datei (.xls / .xlsx)</label>
                            <input type="file" name="file" id="customFile" accept=".xls,.xlsx" class="form-control-file" required>
                        </div>
                    </div>
                </div>

                {{-- Eltern-Import: Sicherheitsbestätigung --}}
                <div x-show="importTyp === 'eltern'" class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmEltern"
                           :required="importTyp === 'eltern'">
                    <label class="form-check-label text-danger font-weight-bold" for="confirmEltern">
                        Ich bestätige, dass alle nicht-geschützten Gruppenverknüpfungen vor dem Import gelöscht werden.
                    </label>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-file-import mr-1"></i> Import starten
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

@endsection

@push('js')
    <script>
        // Einfacher Dateiname-Anzeiger
        document.getElementById('customFile')?.addEventListener('change', function () {
            const label = this.nextElementSibling;
            if (label) label.textContent = this.files[0]?.name || 'Datei wählen';
        });
    </script>

@endpush
