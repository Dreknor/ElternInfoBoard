@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Benutzer importieren
            </h6>
        </div>
        <div class="card-body">
            <p class="text-info">Hinweis Elternimport: Der Import bezieht sich auf eine Individuelle Auswertung der Schulsoftware.
                Ausgehend von den Schülern werden Namen und E-Mail-Adresse sowie Klassenstufe und Lerngruppe der Schüler exportiert.
                Bitte prüfen, ob die Spaltenangabe zur Überschrift passt und ggf. korrigieren.
            </p>

        </div>
        <div class="card-body">
            <form action="{{url('/users/import')}}" method="post" class="form form-horizontal" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Bildung: Klassenstufe</label>
                            <input type="number" name="klassenstufe" step="1" value="2" class="form-control border-input">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Bildung: Klassengruppe</label>
                            <input type="number" name="lerngruppe" step="1" value="3" class="form-control border-input">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>S1: Vorname</label>
                            <input type="number" name="S1Vorname" step="1" value="4" class="form-control border-input">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>S1: Nachname</label>
                            <input type="number" name="S1Nachname" step="1" value="5" class="form-control border-input">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>S1: E-Mail Privat</label>
                            <input type="number" name="S1Email" step="1" value="6" class="form-control border-input">
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>S2: Vorname</label>
                            <input type="number" name="S2Vorname" step="1" value="7" class="form-control border-input">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>S2: Nachname</label>
                            <input type="number" name="S2Nachname" step="1" value="8" class="form-control border-input">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>S2: E-Mail Privat</label>
                            <input type="number" name="S2Email" step="1" value="9" class="form-control border-input">
                        </div>
                    </div>

                </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <div class="">
                                <input type="file"  name="file" id="customFile" accept=".xls,.xlsx">
                            </div>

                        </div>
                    </div>

                <div class="row">
                    <div class="col">
                            <div class="form-group">
                                <label>Import-Typ</label>
                                <select class="custom-select" name="type">
                                    <option value="eltern" selected>Eltern-Import</option>
                                    <option value="mitarbeiter">Mitarbeiter-Import</option>
                                </select>
                            </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            Import starten
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

@endsection

@push('css')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />

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
            "allowedFileExtensions": ['xls', 'xlsx']
        });
    </script>


@endpush