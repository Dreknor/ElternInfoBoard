<div class="tab-pane" id="messenger" role="tabpanel" aria-labelledby="messenger-tab">
    <form action="{{ url('settings/messenger') }}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')

        {{-- Allgemein --}}
        <div class="form-row mt-1 p-2 border bg-light">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 font-weight-bold">
                    <i class="fas fa-clock"></i> Nachrichten-Aufbewahrung (Tage)
                </label>
                <input type="number" class="form-control" name="auto_delete_days"
                       value="{{ $messengerSettings->auto_delete_days }}" min="7" max="3650">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Nachrichten werden nach dieser Anzahl von Tagen automatisch gelöscht (Soft-Delete).
                    Standard: 90 Tage.
                </div>
            </div>
        </div>

        <div class="form-row mt-3 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 font-weight-bold">
                    <i class="fas fa-text-width"></i> Maximale Nachrichtenlänge (Zeichen)
                </label>
                <input type="number" class="form-control" name="max_message_length"
                       value="{{ $messengerSettings->max_message_length }}" min="100" max="10000">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Maximale Anzahl an Zeichen pro Nachricht. Standard: 2000.
                </div>
            </div>
        </div>

        {{-- Direktnachrichten --}}
        <div class="p-3 mt-3 border rounded">
            <h6 class="font-weight-bold text-primary mb-3">
                <i class="fas fa-user"></i> Direktnachrichten
            </h6>
            <div class="form-row">
                <div class="col-md-6">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="allow_direct_messages"
                               name="allow_direct_messages" value="1"
                               {{ $messengerSettings->allow_direct_messages ? 'checked' : '' }}>
                        <label class="custom-control-label" for="allow_direct_messages">
                            Direktnachrichten zwischen Eltern erlauben
                        </label>
                    </div>
                </div>
                <div class="col-md-6 m-auto">
                    <div class="small text-muted">
                        Eltern können 1:1-Nachrichten an andere Mitglieder ihrer Gruppen schicken.
                    </div>
                </div>
            </div>
        </div>

        {{-- Datei-Uploads --}}
        <div class="p-3 mt-3 border rounded">
            <h6 class="font-weight-bold text-secondary mb-3">
                <i class="fas fa-paperclip"></i> Dateianhänge
            </h6>
            <div class="form-row">
                <div class="col-md-6">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="allow_file_uploads"
                               name="allow_file_uploads" value="1"
                               {{ $messengerSettings->allow_file_uploads ? 'checked' : '' }}>
                        <label class="custom-control-label" for="allow_file_uploads">
                            Dateianhänge in Nachrichten erlauben
                        </label>
                    </div>
                </div>
                <div class="col-md-6 m-auto">
                    <div class="small text-muted">
                        Erlaubt das Hochladen von Bildern und Dateien (PDF, DOCX, XLSX) in Nachrichten.
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-6 col-sm-12">
                    <label class="label-control w-100 font-weight-bold">
                        Maximale Dateigröße (MB)
                    </label>
                    <input type="number" class="form-control" name="max_file_size_mb"
                           value="{{ $messengerSettings->max_file_size_mb }}" min="1" max="50">
                </div>
                <div class="col-md-6 col-sm-12 m-auto">
                    <div class="small text-muted">
                        Maximale Dateigröße für Anhänge in Megabyte. Standard: 10 MB.
                    </div>
                </div>
            </div>
        </div>

        {{-- Moderation --}}
        <div class="p-3 mt-3 border rounded border-warning bg-warning bg-opacity-10">
            <h6 class="font-weight-bold text-warning mb-2">
                <i class="fas fa-shield-alt"></i> Moderation
            </h6>
            <p class="small text-muted mb-2">
                Nutzer mit der Berechtigung <code>moderate messages</code> können Nachrichten in Gruppenkonversationen löschen,
                gemeldete Nachrichten prüfen und Nutzer temporär stummschalten.
            </p>
            <a href="{{ route('messenger.admin.reports') }}" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-flag mr-1"></i> Zum Moderationscenter
            </a>
        </div>

        <div class="form-row mt-3">
            <div class="col-12">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-save"></i> Messenger-Einstellungen speichern
                </button>
            </div>
        </div>
    </form>
</div>

