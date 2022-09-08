@extends('layouts.app')
@section('title') - Kontakt @endsection

@section('content')

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Nachricht erstellen an:
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/feedback")}}" method="post" class="form form-horizontal" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <select name="mitarbeiter" class="custom-select">
                                <option value="">Sekretariat</option>
                                @foreach($mitarbeiter as $Mitarbeiter)
                                    <option value="{{$Mitarbeiter->id}}">{{$Mitarbeiter->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <input class="form-control border-input" name="betreff" value="{{old('betreff', 'Nachricht von '.auth()->user()->name)}}">
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

                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="customFile">Datei anfügen</label>
                            <input type="file"  name="files[]" id="customFile" multiple>
                        </div>
                    </div>
                </div>
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

    <div class="card">
        <div class="card-header">
            <h5>Verlauf</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                <tr>
                    <th>
                        Datum
                    </th>
                    <th>
                        An
                    </th>
                    <th>
                        Betreff
                    </th>
                    <th>

                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($emails as $email)
                    <tr>
                        <td>
                            {{$email->created_at->format('d.m.Y H:i')}}
                        </td>
                        <td>
                            {{$email->to}}
                        </td>
                        <td>
                            {{$email->subject}}
                        </td>
                        <td>
                            <div class="row">
                                <div class="col-auto">
                                    <a href="{{url('/feedback/show/'.$email->id)}}" class="card-link">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
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
