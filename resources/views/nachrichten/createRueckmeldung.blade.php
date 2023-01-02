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


    </script>


@endpush
