@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Abfrage erstellen
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/rueckmeldung/$nachricht->id/create/abfrage")}}" method="POST"
                  class="form form-horizontal">
                @csrf
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Empfänger</label>
                            <input type="text" class="form-control border-input" name="empfaenger"
                                   value="{{old('empfaenger')? old('empfaenger') : auth()->user()->email}}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Frage</label>
                            <input type="text" class="form-control border-input" name="description"
                                   value="{{old('description')? old('description') : ""}}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3">
                                <div class="form-group">
                                    <label>Ende</label>
                                    <input type="date" class="form-control border-input" name="ende"
                                           value="{{old('ends', $nachricht->archiv_ab->format('Y-m-d'))}}" required>
                                </div>
                            </div>

                    <div class="col-3">
                                <div class="form-group">
                                    <label>max. Antwortmöglichkeiten (0 für unbegrenzt)</label>
                                    <input type="number" min="0" class="form-control border-input" name="max_number"
                                           value="{{old('max_number',1)}}" required>
                                </div>
                            </div>
                    <div class="col-3">
                                <label class="w-100">Rückmeldung verpflichtend?
                                    <select class="custom-select w-100" name="pflicht">
                                        <option value="0">Nein</option>
                                        <option value="1">Ja</option>
                                    </select>
                                </label>
                            </div>
                    <div class="col-3">
                                <label class="w-100">mehrere Rückmeldungen?
                                    <select class="custom-select w-100" name="multiple">
                                        <option value="0">Nein</option>
                                        <option value="1">Ja</option>
                                    </select>
                                </label>
                            </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-12">
                        <label>Antworten</label>
                        <ul class="list-group">
                            @for($x = 0; $x<3; $x++)
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label class="w-100">Typ
                                                <select class="custom-select w-100" name="types[]">
                                                    <option value="check">Auswahl</option>
                                                    <option value="text">Texteingabe</option>
                                                    <option value="textbox">gr. Textfeld</option>
                                                    <option value="trenner">Trenner (nur zur visuellen Abtrennung)
                                                    </option>
                                                </select>
                                            </label>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="w-100">Antwort
                                                <input type="text" name="options[]" class="form-control p-2">
                                            </label>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="w-100">Pflicht?
                                                <select class="custom-select w-100" name="required[]">
                                                    <option value="0">Nein</option>
                                                    <option value="1">JA</option>
                                                </select>
                                            </label>
                                        </div>
                                    </div>
                                </li>
                            @endfor
                            <li class="list-group-item">
                                <a href="#" class="card-link" id="addOption">
                                    <i class="fa fa-plus-circle"></i> weitere Option anfügen
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            Abfrage erstellen
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

    <script type="text/javascript">
        /**
         * @param {String} HTML representing a single element
         * @return {Element}
         */
        function htmlToElement(html) {
            var template = document.createElement('template');
            html = html.trim(); // Never return a text node of whitespace as the result
            template.innerHTML = html;
            return template.content.firstChild;
        }


        $('#addOption').on('click', function (ev) {
            ev.preventDefault();

            let li = htmlToElement('<li class="list-group-item">' +
                '<div class="row">' +
                '<div class="col-md-2">' +
                '<label class="w-100">Typ' +
                '<select class="custom-select w-100" name="types[]">' +
                '<option value="check">Auswahl</option>' +
                '<option value="text">Texteingabe</option>' +
                '<option value="textbox">gr. Textfeld</option>' +
                '<option value="trenner">Trenner (nur zur visuellen Abtrennung)</option>' +
                '</select>' +
                '</label>' +
                '</div>' +
                '<div class="col-md-8">' +
                '<label class="w-100">Antwort' +
                '<input type="text" name="options[]" class="form-control p-2" >' +
                '</label>' +
                '</div>' +
                '<div class="col-md-2">' +
                '<label class="w-100">Pflicht?' +
                '<select class="custom-select w-100" name="required[]">' +
                '<option value="0">Nein</option>' +
                '<option value="1">JA</option>' +
                '</select>' +
                '</label>' +
                '</div>' +
                '</div>' +
                '</li>')

            let list = ev.target.closest("ul")

            list.insertBefore(li, ev.target.closest('li'));

        })

    </script>

@endpush
