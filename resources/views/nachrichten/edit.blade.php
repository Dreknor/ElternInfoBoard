@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Mitteilung bearbeiten
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/posts/$post->id/$kiosk")}}" method="post" class="form form-horizontal"  enctype="multipart/form-data">
                @csrf
                @method('put')
                <div class="row">
                    <div class="col-md-2 col-sm-12">
                        <div class="form-group">
                            <label>Typ</label>
                            <select class="custom-select" name="type">
                                <option value="info" @if($post->type == "info") selected @endif>Info</option>
                                <option value="pflicht" @if($post->type == "pflicht") selected @endif>Aufgabe - Pflicht</option>
                                <option value="wahl" @if($post->type == "wahl") selected @endif>Aufgabe - Wahl</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label>Überschrift</label>
                            <input type="text" class="form-control border-input" placeholder="Überschrift" name="header" value="{{$post->header}}" required>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-12">
                        <div class="form-group">
                            <label>zuletzt bearbeitet:</label>
                            <input type="datetime" class="form-control border-input date-input" name="updated_at" value="{{\Carbon\Carbon::now()->toDateTimeString()}}" >
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-6">
                        <div class="form-group">
                            <div class="">
                                <label>Autor</label>
                                <select class="custom-select" name="author" id="selectAuthor">
                                    <option value="{{$post->author}}" selected>{{$post->autor->name}}</option>
                                    <option value="{{auth()->user()->id}}">{{auth()->user()->name}}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>Archiv ab</label>
                            <input type="date" class="form-control border-input date-input" name="archiv_ab" value="{{$post->archiv_ab->format('Y-m-d')}}" >
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Nachrichtentext</label>
                            <textarea class="form-control border-input" name="news">
                                {!! $post->news !!}
                            </textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Mitteilung veröffentlichen?</label>
                            <select class="custom-select" name="released">
                                <option value="1" @cannot('release posts') disabled @else @if($post->released ==1) selected @endif @endcannot>Ja</option>
                                @cannot('release posts')
                                    <option value="0" selected>durch Leitung veröffentlichen</option>
                                @else
                                    <option value="0"  @if($post->released ==0) selected @endif>später veröffentlichen</option>
                                @endcannot
                            </select>
                        </div>

                        @include('include.formGroups')

                    </div>

                    <div class="col-md-8 col-sm-12">
                        @can('send urgent message')
                            <div class="row">
                                <div class="col-12">
                                    <div class="card border border-danger">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label>Dringende Nachricht (wird direkt versendet)</label>
                                                        <select class="custom-select" name="urgent">
                                                            <option value="1">Ja</option>
                                                            <option value="" selected>nein</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label for="password">Passwort zur Bestätigung</label>
                                                        <input type="password" class="form-control border-input" name="password" autocomplete="new-password".>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        @endcan
                        <div class="row">
                            @if(count($post->getMedia('header'))>0)
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header border-bottom">
                                            <p>
                                                <b>
                                                    vorhandene Titelbilder (nur das erste wird angezeigt)
                                                </b>
                                            </p>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                @foreach($post->getMedia('header') as $media)
                                                    <li class="list-group-item  list-group-item-action ">
                                                        <a href="{{url('/image/'.$media->id)}}" target="_blank" class="mx-auto ">
                                                            <i class="fas fa-file-download"></i>
                                                            {{$media->name}}
                                                        </a>
                                                        <div class="pull-right btn btn-sm btn-danger fileDelete" data-id="{{$media->id}}">
                                                            <i class="fas fa-times"></i>
                                                        </div>

                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(count($post->getMedia('images'))>0)
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header border-bottom">
                                            <p>
                                                <b>
                                                   vorhandene Bilder
                                                </b>
                                            </p>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                @foreach($post->getMedia('images') as $media)
                                                    <li class="list-group-item  list-group-item-action ">
                                                        <a href="{{url('/image/'.$media->id)}}" target="_blank" class="mx-auto ">
                                                            <i class="fas fa-file-download"></i>
                                                            {{$media->name}}
                                                        </a>
                                                            <div class="pull-right btn btn-sm btn-danger fileDelete" data-id="{{$media->id}}">
                                                                <i class="fas fa-times"></i>
                                                            </div>

                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="">
                                        <label>Datei-Typ</label>
                                        <select class="custom-select" name="collection" id="selectType">
                                            <option value="header">Header-Bild</option>
                                            <option value="images">Bilder</option>
                                            <option value="files" selected>Dateien</option>
                                        </select>
                                        <input type="file"  name="files[]" id="customFile" multiple>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row">

                    @if($post->author == auth()->user()->id)
                        <div class="col-l-10 col-md-8 col-sm-6">
                            <button type="submit" class="btn btn-primary btn-block">
                                Änderungen speichern
                            </button>
                        </div>
                        <div class="col-l-2 col-md-4 col-sm-6">
                            <button type="button" id="nachrichtLoeschen" class="btn btn-danger btn-block" data-id="{{$post->id}}">
                                Nachricht löschen
                            </button>
                        </div>
                    @else
                        <div class="col-l-12 col-md-12 col-sm-12">
                            <button type="submit" class="btn btn-primary btn-block">
                                Änderungen speichern
                            </button>
                        </div>
                    @endif
                </div>
            </form>

        </div>
    </div>

    @if(is_null($post->rueckmeldung))
        <div class="row" id="createButtons">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <a href="{{url("rueckmeldungen/$post->id/createImageUpload")}}" id="bilderButton" class="btn btn-block btn-outline-primary">
                                    Bilder-Upload ermöglichen
                                </a>
                            </div>
                            <div class="col-6">
                                <a id="rueckmeldungButton" class="btn btn-block btn-outline-primary">
                                    Rückmeldung erstellen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row d-none" id="createForm">
            <div class="col-12">
                <div class="card" id="rueckmeldungCard">
                    <div class="card-header">
                        <h6 class="card-title">
                            Rückmeldung
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="card-body">
                            <form action="{{url("/rueckmeldung/$post->id/createImage")}}" method="post" class="form form-horizontal">
                                @csrf
                                @method('put')
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Empfänger</label>
                                            <input type="email" class="form-control border-input" name="empfaenger" value="{{old('empfaenger')}}" required >
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
                                                <option value="0" selected>Nein</option>
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
                                    <div class="col">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            Rückmeldung erstellen
                                        </button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    @elseif(!is_null($post->rueckmeldung) and $post->rueckmeldung->type == 'bild')
        <div class="card" id="rueckmeldungCard">
            <div class="card-header">
                <h6>
                    Bilder-Upload entfernen?
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        @if( $post->rueckmeldung->commentable)
                            <a href="{{url("rueckmeldungen/".$post->rueckmeldung->id."/commentable")}}" class="btn btn-warning btn-block">
                                Kommentare abschalten
                            </a>
                        @else
                            <a href="{{url("rueckmeldungen/".$post->rueckmeldung->id."/commentable")}}" class="btn btn-primary btn-block">
                                Kommentare erlauben
                            </a>
                        @endif
                    </div>
                    <div class="col-6">
                        <div class="btn btn-danger btn-block" id="rueckmeldungLoeschen" data-id="{{$rueckmeldung->id}}" >
                            Bilder-Upload endgültig löschen
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @else
        <div class="card" id="rueckmeldungCard">
        <div class="card-header">
            <h6 class="card-title">
                Rückmeldung
            </h6>
        </div>
        <div class="card-body">
            <div class="card-body">
                <form action="{{url("/rueckmeldung/$post->id/create")}}" method="post" class="form form-horizontal">
                    @csrf
                    @method('put')
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Empfänger</label>
                                <input type="email" class="form-control border-input" name="empfaenger" value="{{old('empfaenger')? old('empfaenger') : $rueckmeldung->empfaenger}}" required >
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ende</label>
                                <input type="date" class="form-control border-input" name="ende" value="{{optional($rueckmeldung->ende)->format('Y-m-d')}}" required >
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Rückmeldung verpflichtend?</label>
                                <select class="custom-select" name="pflicht">
                                    <option value="0" >Nein</option>
                                    <option value="1" @if($rueckmeldung and $rueckmeldung->pflicht ==1) selected @endif>Ja</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Rückmeldung</label>
                                <textarea class="form-control border-input" name="text">
                                {{optional($rueckmeldung)->text}}
                            </textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button type="submit" class="btn btn-primary btn-block">
                                Rückmeldung erstellen
                            </button>
                        </div>
                        @if(!is_null($post->rueckmeldung))
                            <div class="col">
                                <div class="btn btn-danger btn-block" id="rueckmeldungLoeschen" data-id="{{$rueckmeldung->id}}" @if(count($post->userRueckmeldung)>0) disabled  @endif>
                                    @if(count($post->userRueckmeldung)>0) Es wurden bereits Rückmeldungen abgegeben @else Rückmeldung löschen @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>
    @endif

@endsection

@push('css')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />

@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/piexif.min.js" type="text/javascript"></script>

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
                'contextmenu media',
            ],
            link_class_list: [
                {title: 'None', value: ''},
                {title: 'Button groß', value: 'btn btn-primary btn-block'},
                {title: 'Button normal', value: 'btn btn-primary'}
            ],
            link_list: [
                {title: 'Listen', value: '{{url('listen')}}'},
                {title: 'Downloads', value: '{{url('files')}}'}
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link | media',
            contextmenu: " link image inserttable | cell row column deletetable | pageembed",
            @if(auth()->user()->can('use scriptTag'))
            extended_valid_elements : ["script[src|async|defer|type|charset]",
                "iframe[frameborder|src|width|height|name|align|id|class|style]"],
            @endif

        });</script>



    <!-- piexif.min.js is needed for auto orienting image files OR when restoring exif data in resized images and when you
        wish to resize images before upload. This must be loaded before fileinput.min.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/piexif.min.js" type="text/javascript"></script>
    <!-- sortable.min.js is only needed if you wish to sort / rearrange files in initial preview.
        This must be loaded before fileinput.min.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/sortable.min.js" type="text/javascript"></script>
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
            maxFileSize: @if(auth()->user()->can('upload great files')) {{config('medialibrary.max_file_size')}} @else 3000 @endif ,
            'theme': "fas",
        });
    </script>


        <script src="{{asset('js/plugins/sweetalert2.all.min.js')}}"></script>

        <script>
            $('.fileDelete').on('click', function () {
                var fileId = $(this).data('id');
                var button = $(this);

                swal.fire({
                    title: "Datei wirklich entfernen?",
                    type: "warning",
                    showCancelButton: true,
                    cancelButtonText: "Datei behalten",
                    confirmButtonText: "Datei entfernen!",
                    confirmButtonColor: "danger"
                }).then((confirmed) => {
                    if (confirmed.value) {
                        $.ajax({
                            url: '{{url("/file/")}}'+'/'+fileId,
                            type: 'DELETE',
                            data: {
                                "_token": "{{csrf_token()}}",
                            },
                            success: function(result) {
                                $(button).parent('li').fadeOut();
                            }
                        });
                    }
                });
            });

        </script>

    <script>
        $('#rueckmeldungLoeschen').on('click', function () {
            var rueckmeldungId = $(this).data('id');
            var button = $(this);

            swal.fire({
                title: "Rückmeldung wirklich entfernen?",
                type: "warning",
                showCancelButton: true,
                cancelButtonText: "Abbrechen",
                confirmButtonText: "Rückmeldung entfernen!",
                confirmButtonColor: "danger"
            }).then((confirmed) => {
                if (confirmed.value) {
                    $.ajax({
                        url: '{{url("/rueckmeldung/")}}'+'/'+rueckmeldungId,
                        type: 'DELETE',
                        data: {
                            "_token": "{{csrf_token()}}",
                        },
                        success: function(result) {
                            $('#rueckmeldungCard').fadeOut();
                        }
                    });
                }
            });
        });

    </script>

    <script>
        $('#nachrichtLoeschen').on('click', function () {
            var nachrichtId = $(this).data('id');
            var button = $(this);

            swal.fire({
                title: "Nachricht wirklich löschen?",
                type: "warning",
                showCancelButton: true,
                cancelButtonText: "Abbrechen",
                confirmButtonText: "Nachricht entgültig löschen!",
                confirmButtonColor: "danger"
            }).then((confirmed) => {
                if (confirmed.value) {
                    $.ajax({
                        url: '{{url("/posts/")}}'+'/'+nachrichtId,
                        type: 'DELETE',
                        data: {
                            "_token": "{{csrf_token()}}",
                        },
                        success: function(result) {
                            location.replace("{{url('home')}}");
                        }
                    });
                }
            });
        });

    </script>

    <!-- Edit Date for Safari-User-->
    <script>
        $('.date-input').on('change', function (event) {
            event.target.value = event.target.value.substr(0, 19);
        })
    </script>

    <script>
        $('#rueckmeldungButton').on('click', function () {
                $('#createButtons').addClass('d-none');
                $('#createForm').removeClass('d-none');
        })
    </script>
@endpush
