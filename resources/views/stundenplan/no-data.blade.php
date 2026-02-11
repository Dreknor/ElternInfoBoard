@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> Stundenplan
                    </h4>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-info-circle fa-5x text-info mb-4"></i>

                    <h3 class="mb-3">Noch kein Stundenplan vorhanden</h3>

                    <p class="text-muted mb-4">
                        {{ $message ?? 'Es wurde noch kein Stundenplan importiert.' }}
                    </p>

                    @can('edit stundenplan')
                        <div class="alert alert-info">
                            <h5><i class="fas fa-lightbulb"></i> So importieren Sie einen Stundenplan:</h5>

                            <div class="text-left mt-3">
                                <strong>Option 1: Web-Import</strong>
                                <ol class="mt-2">
                                    <li>Klicken Sie auf den Button "Stundenplan importieren" unten</li>
                                    <li>Laden Sie eine JSON-Datei hoch</li>
                                    <li>Der Stundenplan wird in die Datenbank importiert</li>
                                </ol>

                                <strong class="d-block mt-3">Option 2: API-Import</strong>
                                <ol class="mt-2">
                                    <li>Gehen Sie zu <a href="{{ url('settings') }}#stundenplan-tab">Einstellungen → Stundenplan</a></li>
                                    <li>Kopieren Sie die API-URL mit Key</li>
                                    <li>Senden Sie die JSON-Daten per POST-Request</li>
                                </ol>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('stundenplan.import') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload"></i> Stundenplan importieren
                            </a>

                            <a href="{{ url('settings') }}#stundenplan-tab" class="btn btn-outline-secondary btn-lg ml-2">
                                <i class="fas fa-cog"></i> Einstellungen
                            </a>
                        </div>
                    @else
                        <p class="text-muted mt-4">
                            <i class="fas fa-info-circle"></i>
                            Bitte wenden Sie sich an einen Administrator, um einen Stundenplan zu importieren.
                        </p>
                    @endcan
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> Hilfe</h5>
                </div>
                <div class="card-body">
                    <h6>Welche Datenformate werden unterstützt?</h6>
                    <ul>
                        <li><strong>Indiware Gesamtexport</strong> (wird automatisch konvertiert)</li>
                        <li><strong>Direktes JSON-Format</strong> mit Basisdaten, Zeitslots und Klassen</li>
                    </ul>

                    <h6 class="mt-3">Wo finde ich die Dokumentation?</h6>
                    <p class="mb-0">
                        Die vollständige Dokumentation finden Sie in <code>docs/STUNDENPLAN_IMPORT.md</code>
                        oder in der <a href="{{ url('settings') }}#stundenplan-tab">Stundenplan-Einstellungen</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

