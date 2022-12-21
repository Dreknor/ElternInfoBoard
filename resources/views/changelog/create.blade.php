@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h6 class="card-title">
            neues Changelog verfassen
        </h6>
    </div>
    @if ($errors->any())
        <div class="card-body">
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

    @endif
    <div class="card-body">
        <form action="{{url('/changelog')}}" method="post" class="form form-horizontal" enctype="multipart/form-data" id="nachrichtenForm">
            @csrf
            <div class="row">
                <div class="col-md-8 col-sm-12">
                    <div class="form-group">
                        <label>Überschrift</label>
                        <input type="text" class="form-control border-input" placeholder="Überschrift" name="header" value="{{old('header')}}" required>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="form-group">
                        <label>Ändert Benutzereinstellungen</label>
                        <select class="custom-select" name="changeSettings" id="changeSettings">
                                <option value="0" selected>nein</option>
                                <option value="1" >ja</option>

                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Changelog</label>
                        <textarea class="form-control border-input" name="text">
                            {{old('text')}}
                        </textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                        Speichern
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

@endsection

@push('css')

    <link href="{{asset('css/fileinput.min.css')}}" media="all" rel="stylesheet" type="text/css" />

@endpush

@push('js')
    <script src="{{asset('js/piexif.min.js')}}" type="text/javascript"></script>

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink lists link charmap',
                'searchreplace visualblocks code',
                'insertdatetime table paste code wordcount',
                'contextmenu',
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | ',
            contextmenu: " link image inserttable | cell row column deletetable",
            @if(auth()->user()->can('use scriptTag'))
            extended_valid_elements : "script[src|async|defer|type|charset]",
            @endif

        });</script>



    <!-- piexif.min.js is needed for auto orienting image files OR when restoring exif data in resized images and when you
        wish to resize images before upload. This must be loaded before fileinput.min.js -->
    <script src="{{asset('js/piexif.min.js')}}" type="text/javascript"></script>
    <!-- sortable.min.js is only needed if you wish to sort / rearrange files in initial preview.
        This must be loaded before fileinput.min.js -->
    <script src="{{asset('js/plugins/sortable.min.js')}}" type="text/javascript"></script>
    <!-- purify.min.js is only needed if you wish to purify HTML content in your preview for
        HTML files. This must be loaded before fileinput.min.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/purify.min.js" type="text/javascript"></script>
    <!-- popper.min.js below is needed if you use bootstrap 4.x (for popover and tooltips). You can also use the bootstrap js
       3.3.x versions without popper.min.js. -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/fileinput.min.js"></script>
    <!-- following theme script is needed to use the Font Awesome 5.x theme (`fas`) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/themes/fas/theme.min.js"></script>

    <script>
        // initialize with defaults

        $("#customFile").fileinput({
            'showUpload':false,
            'previewFileType':'any',
            maxFileSize: @if(auth()->user()->can('upload great files')) 300000 @else 3000 @endif ,
            'theme': "fas",
        });
    </script>

    <script>
        $('#veroeffentlichenSelect').change(function(){
            $('#veroeffentlichenSelect option:selected').each(function(){
                if($(this).text() === "Ja"){
                    $('#submitBtn').text('Beitrag veröffentlichen');
                } else {
                    $('#submitBtn').text('Beitrag speichern');

                }
            });
        });

    </script>

    <script>
        $('#submitBtn').on('click', function (event) {
            $("#nachrichtenForm").submit();
        })
    </script>

    <script>
        $('.date-input').on('change', function (event) {
            event.target.value = event.target.value.substr(0, 19);
        })
    </script>
@endpush
