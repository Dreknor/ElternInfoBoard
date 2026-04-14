<div class="tab-pane" id="reminder" role="tabpanel" aria-labelledby="reminder-tab">
    <form action="{{ url('settings/reminder') }}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')

        {{-- Versandzeit --}}
        <div class="form-row mt-1 p-2 border bg-light">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 font-weight-bold">
                    <i class="far fa-clock"></i> Versandzeit für Erinnerungen
                </label>
                <input type="time" class="form-control" name="send_time"
                       value="{{ $reminderSettings->send_time }}">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zu welcher Uhrzeit sollen die automatischen Erinnerungen täglich versendet werden?
                </div>
            </div>
        </div>

        {{-- Stufe 1 --}}
        <div class="p-3 mt-3 border rounded">
            <h6 class="font-weight-bold text-info mb-3">
                <i class="fas fa-bell"></i> Stufe 1: Sanfte Erinnerung
            </h6>

            <div class="form-row">
                <div class="col-md-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="level1_active" name="level1_active" value="1"
                               {{ $reminderSettings->level1_active ? 'checked' : '' }}>
                        <label class="custom-control-label" for="level1_active">Aktiviert</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="small">Tage vor Frist</label>
                    <input type="number" class="form-control form-control-sm" name="level1_days_before_deadline"
                           value="{{ $reminderSettings->level1_days_before_deadline }}" min="1" max="30">
                </div>
                <div class="col-md-6">
                    <label class="small">Kanäle</label>
                    <div class="d-flex gap-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="level1_in_app" name="level1_in_app" value="1"
                                   {{ $reminderSettings->level1_in_app ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level1_in_app">In-App</label>
                        </div>
                        <div class="custom-control custom-checkbox ml-3">
                            <input type="checkbox" class="custom-control-input" id="level1_email" name="level1_email" value="1"
                                   {{ $reminderSettings->level1_email ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level1_email">E-Mail</label>
                        </div>
                        <div class="custom-control custom-checkbox ml-3">
                            <input type="checkbox" class="custom-control-input" id="level1_push" name="level1_push" value="1"
                                   {{ $reminderSettings->level1_push ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level1_push">Push</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stufe 2 --}}
        <div class="p-3 mt-3 border rounded border-warning">
            <h6 class="font-weight-bold text-warning mb-3">
                <i class="fas fa-exclamation-circle"></i> Stufe 2: Dringende Erinnerung
            </h6>

            <div class="form-row">
                <div class="col-md-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="level2_active" name="level2_active" value="1"
                               {{ $reminderSettings->level2_active ? 'checked' : '' }}>
                        <label class="custom-control-label" for="level2_active">Aktiviert</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="small">Tage vor Frist</label>
                    <input type="number" class="form-control form-control-sm" name="level2_days_before_deadline"
                           value="{{ $reminderSettings->level2_days_before_deadline }}" min="1" max="30">
                </div>
                <div class="col-md-6">
                    <label class="small">Kanäle</label>
                    <div class="d-flex gap-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="level2_in_app" name="level2_in_app" value="1"
                                   {{ $reminderSettings->level2_in_app ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level2_in_app">In-App</label>
                        </div>
                        <div class="custom-control custom-checkbox ml-3">
                            <input type="checkbox" class="custom-control-input" id="level2_email" name="level2_email" value="1"
                                   {{ $reminderSettings->level2_email ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level2_email">E-Mail</label>
                        </div>
                        <div class="custom-control custom-checkbox ml-3">
                            <input type="checkbox" class="custom-control-input" id="level2_push" name="level2_push" value="1"
                                   {{ $reminderSettings->level2_push ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level2_push">Push</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stufe 3 --}}
        <div class="p-3 mt-3 border rounded border-danger">
            <h6 class="font-weight-bold text-danger mb-3">
                <i class="fas fa-exclamation-triangle"></i> Stufe 3: Letzte Erinnerung + Eskalation
            </h6>

            <div class="form-row">
                <div class="col-md-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="level3_active" name="level3_active" value="1"
                               {{ $reminderSettings->level3_active ? 'checked' : '' }}>
                        <label class="custom-control-label" for="level3_active">Aktiviert</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="small">Tage <strong>vor</strong> Frist</label>
                    <input type="number" class="form-control form-control-sm" name="level3_days_before_deadline"
                           value="{{ $reminderSettings->level3_days_before_deadline }}" min="0" max="30">
                </div>
                <div class="col-md-6">
                    <label class="small">Kanäle</label>
                    <div class="d-flex gap-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="level3_in_app" name="level3_in_app" value="1"
                                   {{ $reminderSettings->level3_in_app ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level3_in_app">In-App</label>
                        </div>
                        <div class="custom-control custom-checkbox ml-3">
                            <input type="checkbox" class="custom-control-input" id="level3_email" name="level3_email" value="1"
                                   {{ $reminderSettings->level3_email ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level3_email">E-Mail</label>
                        </div>
                        <div class="custom-control custom-checkbox ml-3">
                            <input type="checkbox" class="custom-control-input" id="level3_push" name="level3_push" value="1"
                                   {{ $reminderSettings->level3_push ? 'checked' : '' }}>
                            <label class="custom-control-label" for="level3_push">Push</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row mt-3">
                <div class="col-md-6">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="level3_escalate_to_author" name="level3_escalate_to_author" value="1"
                               {{ $reminderSettings->level3_escalate_to_author ? 'checked' : '' }}>
                        <label class="custom-control-label" for="level3_escalate_to_author">
                            Autor per E-Mail benachrichtigen
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted">
                        Bei Stufe 3 wird der Autor der Nachricht benachrichtigt, wenn Rückmeldungen nach Fristablauf noch fehlen.
                    </div>
                </div>
            </div>
        </div>

        {{-- Einbezogene Typen --}}
        <div class="form-row mt-3 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 font-weight-bold">
                    <i class="fas fa-filter"></i> Einbezogene Erinnerungstypen
                </label>
                <div class="mt-2">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="include_rueckmeldungen" name="include_rueckmeldungen" value="1"
                               {{ $reminderSettings->include_rueckmeldungen ? 'checked' : '' }}>
                        <label class="custom-control-label" for="include_rueckmeldungen">Pflicht-Rückmeldungen</label>
                    </div>
                    <div class="custom-control custom-checkbox mt-1">
                        <input type="checkbox" class="custom-control-input" id="include_read_receipts" name="include_read_receipts" value="1"
                               {{ $reminderSettings->include_read_receipts ? 'checked' : '' }}>
                        <label class="custom-control-label" for="include_read_receipts">Lesebestätigungen</label>
                    </div>
                    <div class="custom-control custom-checkbox mt-1">
                        <input type="checkbox" class="custom-control-input" id="include_attendance_queries" name="include_attendance_queries" value="1"
                               {{ $reminderSettings->include_attendance_queries ? 'checked' : '' }}>
                        <label class="custom-control-label" for="include_attendance_queries">Anwesenheitsabfragen</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wählen Sie aus, welche Rückmeldungstypen in das automatische Erinnerungssystem einbezogen werden sollen.
                </div>
            </div>
        </div>

        <div class="form-row mt-3">
            <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-save"></i> Erinnerungseinstellungen speichern
            </button>
        </div>
    </form>
</div>

