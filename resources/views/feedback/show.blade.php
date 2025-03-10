@extends('layouts.app')
@section('title') - Kontakt @endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>
                        Nachricht erstellen an:
                    </h5>
                </div>

                <div class="card-body">
                    <form action="{{url("/feedback")}}" method="post" class="form form-horizontal"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <select name="mitarbeiter" class="custom-select">
                                        <option value="">Sekretariat</option>
                                        @foreach($mitarbeiter->sortBy('FamilieName') as $Mitarbeiter)
                                            <option value="{{$Mitarbeiter->id}}">
                                                {{$Mitarbeiter->familieName}}, {{$Mitarbeiter->vorname}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input class="form-control border-input" name="betreff"
                                           value="{{old('betreff', 'Nachricht von '.auth()->user()->name)}}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                            <textarea class="form-control border-input" name="text">
                                {{old('text')}}
                            </textarea>
                                </div>
                            </div>
                        </div>
<!--
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="customFile">Datei anfügen</label>
                                    <input type="file"  name="files[]" id="customFile" multiple>
                                </div>
                            </div>
                        </div>
                        -->
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block">
                                    Feedback senden
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
    @can('see mails')
        <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>
                        alte Nachrichten
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($mails as $mail)
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-9">
                                        <h6>
                                            {{$mail->subject}}
                                        </h6>
                                    </div>
                                    <div class="col-2">
                                        {{$mail->created_at->format('d.m.Y H:i')}}
                                    </div>
                                    @can('see mails')
                                        <div class="col-1">
                                            <form action="{{url('/feedback/'.$mail->id)}}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <p>
                                            {!! $mail->text !!}
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <ul class="list-group">
                                            @foreach($mail->getMedia('files') as $file)
                                                <li class="list-group-item">
                                                    <a href="{{url('/image/'.$file->id)}}" target="_blank" class="mx-auto ">
                                                        <i class="fas fa-file-download"></i>
                                                        {{$file->name}}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>

                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endcan

</div>



@endsection


@push('css')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/css/fileinput.min.css" media="all"
          rel="stylesheet" type="text/css"/>

@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/piexif.min.js" type="text/javascript"></script>

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
            maxFileSize: 3000,
            'theme': "fas",
        });
    </script>


    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            height: 500,
            menubar: false,
        });</script>



    <script>


    </script>


@endpush
