@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h6 class="card-title">
            neue Mitteilung verfassen
        </h6>
    </div>
    <div class="card-body">
        <form action="{{url('/posts')}}" method="post" class="form form-horizontal" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Überschrift</label>
                        <input type="text" class="form-control border-input" placeholder="Überschrift" name="header" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Nachrichtentext</label>
                        <textarea class="form-control border-input" name="news"></textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Mitteilung veröffentlichen?</label>
                        <select class="custom-select" name="released">
                            <option value="1">Ja</option>
                            <option value="0">Später veröffentlichen</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Für welche Gruppen?</label>
                        <br>
                        <input type="checkbox" name="gruppen[]" value="all" id="checkboxAll"/>
                        <label for="checkboxAll" id="labelCheckAll"><b>Alle Gruppen</b></label>



                    @foreach($gruppen as $gruppe)
                            <div>
                                <input type="checkbox" id="{{$gruppe->name}}" name="gruppen[]" value="{{$gruppe->id}}">
                                <label for="{{$gruppe->name}}">{{$gruppe->name}}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <div class="">
                            <label>Datei-Typ</label>
                            <select class="custom-select" name="collection">
                                <option value="images">Bilder</option>
                                <option value="files" selected>Dateien</option>
                            </select>
                            <input type="file"  name="files[]" id="customFile" multiple>
                        </div>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-block">
                        Absenden
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

@endsection

@section('css')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />

@endsection

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

    <script src="{{asset('js/plugins/fileinput.min.js')}}"></script>
    <script src="{{asset('js/plugins/theme.min.js')}}"></script>


    <script>
        // initialize with defaults
        $("#customFile").fileinput({
            'showUpload':false,
            'previewFileType':'any',
            theme: "fas",
        });
    </script>


@endpush