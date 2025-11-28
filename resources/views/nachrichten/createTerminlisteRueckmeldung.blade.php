@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Terminlisten-Rückmeldung einrichten
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/rueckmeldung/$nachricht->id/create/terminliste")}}" method="post" class="form form-horizontal">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Terminliste <span class="text-danger">*</span></label>
                            <select class="custom-select" name="liste_id" id="liste_id" required>
                                <option value="" disabled selected>Liste auswählen</option>
                                @foreach($terminlisten as $liste)
                                    <option value="{{$liste->id}}"
                                            data-multiple="{{$liste->multiple ? 1 : 0}}"
                                            @selected(old('liste_id') == $liste->id)>
                                        {{$liste->listenname}} ({{ $liste->termine->where('reserviert_fuer', null)->count() }} freie Termine)
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Nur aktive Listen mit freien Terminen stehen zur Auswahl.</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Rückmeldung verpflichtend?</label>
                            <select class="custom-select" name="pflicht">
                                <option value="0" selected>Nein</option>
                                <option value="1">Ja</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Startdatum (Anzeige) <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control border-input"
                                   name="terminliste_start_date"
                                   id="terminliste_start_date"
                                   value="{{ old('terminliste_start_date', \Carbon\Carbon::today()->format('Y-m-d')) }}"
                                   min="{{\Carbon\Carbon::today()->format('Y-m-d')}}"
                                   required>
                            <small class="text-muted">Ab diesem Datum werden Termine angezeigt.</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Enddatum (Anzeige) <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control border-input"
                                   name="terminliste_end_date"
                                   id="terminliste_end_date"
                                   value="{{ old('terminliste_end_date', \Carbon\Carbon::today()->format('Y-m-d')) }}"
                                   min="{{\Carbon\Carbon::today()->format('Y-m-d')}}"
                                   required>
                            <small class="text-muted">Bis zu diesem Datum werden Termine angezeigt.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Frist für Buchungen <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control border-input"
                                   name="ende"
                                   value="{{old('ende', $nachricht->archiv_ab?->format('Y-m-d'))}}"
                                   required>
                            <small class="text-muted">Bis zu diesem Datum können Nutzer Termine buchen.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info d-none" id="terminlisteMultipleHint">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Mehrfachbuchungen erlaubt:</strong> Nutzer können mehrere Termine aus dieser Liste buchen.
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Hinweis:</strong> Termine können nur in der Nachrichtenansicht gebucht werden. Das Löschen von Buchungen erfolgt weiterhin über die Terminlistenverwaltung.
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            Terminlisten-Rückmeldung erstellen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startInput = document.getElementById('terminliste_start_date');
        const endInput = document.getElementById('terminliste_end_date');
        const select = document.getElementById('liste_id');
        const multipleHint = document.getElementById('terminlisteMultipleHint');

        // Synchronisiere Ende mit Start
        startInput.addEventListener('change', function () {
            if (!endInput.value || endInput.value < startInput.value) {
                endInput.value = startInput.value;
            }
            endInput.min = startInput.value;
        });

        // Zeige Hinweis bei Mehrfach-Listen
        select.addEventListener('change', function() {
            const option = select.options[select.selectedIndex];
            const allowsMultiple = option.dataset.multiple === '1';

            if (allowsMultiple) {
                multipleHint.classList.remove('d-none');
            } else {
                multipleHint.classList.add('d-none');
            }
        });
    });
</script>
@endpush

