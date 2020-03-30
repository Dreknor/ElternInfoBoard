<div class="card-footer">
    <div class="row">
        <div class="@if ($nachricht->rueckmeldung->commentable) col-sm-12 col-md-4 col-lg-4 @else col-12 @endif">
            <div class="card">
                <div class="card-body">
                    <form action="{{url("/rueckmeldung/$nachricht->id/saveFile")}}" method="post" class="form form-horizontal"  enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <div class="">
                                <label>Bezeichnung</label>
                                <input type="text"  name="name" id="nameOfFile_{{$nachricht->id}}" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="">
                                <label>Datei</label>
                                <input type="file"  name="files" id="customFile_{{$nachricht->id}}" class="fileinput">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block" id="btnSave_nachricht_{{$nachricht->id}}">
                                    Bild hochladen
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($nachricht->rueckmeldung->commentable)
            <div class="col-sm-12 col-md-8 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        @include('nachrichten.footer.comments')
                    </div>
                </div>

            </div>
        @endif
</div>




@section('css')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />
    <link href="{{asset('css/comments.css')}}" media="all" rel="stylesheet" type="text/css" />

@endsection

@section('js')
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




@endsection

@push('js')

    <script>
        // initialize with defaults

        $("#customFile_{{$nachricht->id}}").fileinput({
            'showUpload':false,
            'previewFileType':'images',
            maxFileSize: 3000,
            allowedFileExtensions: ["jpg", "png", "gif"],
            'theme': "fas",
        });
    </script>


@endpush
