@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Abfrage bearbeiten
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/rueckmeldung/$rueckmeldung->id/updateAbfrage")}}" method="POST"
                  class="form form-horizontal">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Empfänger</label>
                            <input type="text" class="form-control border-input" name="empfaenger"
                                   value="{{old('empfaenger', $rueckmeldung->empfaenger)? old('empfaenger', $rueckmeldung->empfaenger) : auth()->user()->email}}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Frage</label>
                            <input type="text" class="form-control border-input" name="description"
                                   value="{{old('description', $rueckmeldung->text)? old('description', $rueckmeldung->text) : ""}}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Ende</label>
                                    <input type="date" class="form-control border-input" name="ende"
                                           value="{{old('ends', $rueckmeldung->ende->format('Y-m-d'))}}" required>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="form-group">
                                    <label>max. Antwortmöglichkeiten (0 für unbegrenzt)</label>
                                    <input type="number" min="0" class="form-control border-input" name="max_number"
                                           value="{{old('max_number', $rueckmeldung->max_answers)}}" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="w-100">Rückmeldung verpflichtend?
                                    <select class="custom-select w-100" name="pflicht">
                                        <option value="0">Nein</option>
                                        <option value="1" @if($rueckmeldung->pflicht == 1) selected @endif>Ja</option>
                                    </select>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="w-100">mehrere Rückmeldungen?
                                    <select class="custom-select w-100" name="multiple">
                                        <option value="0">Nein</option>
                                        <option value="1" @if($rueckmeldung->multiple == 1) selected @endif>Ja</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-8">
                        <label>Antworten</label>
                        <ul class="list-group">
                            @foreach($rueckmeldung->options as $option)
                                <li class="list-group-item" id="option_{{$option->id}}">
                                    <div class="row">
                                        <div class="col-4">
                                            <label class="w-100">Typ
                                                <select class="custom-select w-100" name="types[]">
                                                    <option value="check" @if($option->type == 'check') selected @endif>
                                                        Auswahl
                                                    </option>
                                                    <option value="text" @if($option->type == 'text') selected @endif>
                                                        Texteingabe
                                                    </option>
                                                </select>
                                            </label>
                                        </div>
                                        <div class="col-7">
                                            <label class="w-100">Antwort
                                                <input type="text" name="options[]" class="form-control"
                                                       value="{{$option->option}}">
                                            </label>
                                        </div>
                                        <div class="col-1">
                                            <a href="#" class="text-danger link-danger pull-right option_delete"
                                               onclick="deleteOption({{$option->id}}, event)">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
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

        function deleteOption(option, event) {
            event.preventDefault();


            const li = document.getElementById('option_' + option)
            li.remove()
        }

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
                '<div class="col-4">' +
                '<label class="w-100">Typ' +
                '<select class="custom-select w-100" name="types[]">' +
                '<option value="check">Auswahl</option>' +
                '<option value="text">Texteingabe</option>' +
                '</select>' +
                '</label>' +
                '</div>' +
                '<div class="col-8">' +
                '<label class="w-100">Antwort' +
                '<input type="text" name="options[]" class="form-control" >' +
                '</label>' +
                '</div>' +
                '</div>' +
                '</li>')

            let list = ev.target.closest("ul")

            list.insertBefore(li, ev.target.closest('li'));

        })

    </script>

@endpush
