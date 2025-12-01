@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Rückmeldung verfassen
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/rueckmeldung/$nachricht->id/create")}}" method="post" class="form form-horizontal">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Empfänger</label>
                            <input type="email" class="form-control border-input" name="empfaenger" value="{{old('empfaenger')? old('empfaenger') : "info@esz-radebeul.de"}}" required >
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Ende</label>
                            <input type="date" class="form-control border-input" name="ende" value="{{old('ende')}}" required >
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Rückmeldung verpflichtend?</label>
                            <select class="custom-select" name="pflicht">
                                <option value="0">Nein</option>
                                <option value="1">Ja</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Rückmeldung</label>
                            <textarea class="form-control border-input" name="text">
                                {{old('text')}}
                            </textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Terminliste</label>
                            <select class="custom-select" name="liste_id" required>
                                <option value="" disabled selected>Liste auswählen</option>
                                @foreach($terminlisten as $liste)
                                    <option value="{{$liste->id}}" data-multiple="{{$liste->multiple ? 1 : 0}}"
                                            @selected(old('liste_id') == $liste->id)
                                            @disabled($liste->termine->where('reserviert_fuer', null)->count() === 0)
                                    >
                                        {{$liste->listenname}} ({{ $liste->termine->where('reserviert_fuer', null)->count() }} frei)
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Nur aktive Listen mit freien Terminen stehen zur Auswahl.</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Startdatum (Anzeige)</label>
                            <input type="date" class="form-control border-input" name="terminliste_start_date" id="terminliste_start_date" value="{{ old('terminliste_start_date') }}" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Enddatum (Anzeige)</label>
                            <input type="date" class="form-control border-input" name="terminliste_end_date" id="terminliste_end_date" value="{{ old('terminliste_end_date') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info d-flex align-items-center" id="terminlisteMultipleHint" style="display: none;">
                            <span class="mr-2"><i class="fas fa-info-circle"></i></span>
                            <span>Mehrfachbuchungen sind erlaubt, da die ausgewählte Liste mehrere Einträge pro Person zulässt.</span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            Rückmeldung erstellen
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

@endsection

@push('css')


@endpush

@push('js')

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink lists link charmap anchor',
                'searchreplace visualblocks code',
                'insertdatetime table paste code wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',

        });</script>



    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('terminliste_start_date');
            const endInput = document.getElementById('terminliste_end_date');
            const select = document.querySelector('select[name="liste_id"]');
            const multipleHint = document.getElementById('terminlisteMultipleHint');

            startInput.addEventListener('change', function () {
                if (!endInput.value || endInput.value < startInput.value) {
                    endInput.value = startInput.value;
                }
                endInput.min = startInput.value;
            });

            select.addEventListener('change', function() {
                const option = select.options[select.selectedIndex];
                const allowsMultiple = option.dataset.multiple === '1';

                if (allowsMultiple) {
                    multipleHint.style.display = 'flex';
                } else {
                    multipleHint.style.display = 'none';
                }
            });
        });
    </script>


@endpush
