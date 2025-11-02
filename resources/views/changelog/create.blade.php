@extends('layouts.app')
@section('title') - Neues Changelog @endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 border-b border-purple-800">
            <h5 class="text-xl font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-plus-circle"></i>
                Neues Changelog verfassen
            </h5>
        </div>

        @if ($errors->any())
            <div class="p-6 border-b border-gray-200">
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle text-red-600 mt-1"></i>
                        <ul class="text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="p-8">
            <form action="{{url('/changelog')}}" method="post" class="space-y-6" enctype="multipart/form-data" id="nachrichtenForm">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading text-purple-600 mr-1"></i>
                            Überschrift
                            <span class="text-red-600">*</span>
                        </label>
                        <input type="text"
                               class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 outline-none"
                               placeholder="z.B. Neue Funktionen im November 2025"
                               name="header"
                               value="{{old('header')}}"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-cog text-purple-600 mr-1"></i>
                            Einstellungen ändern
                            <span class="block text-xs text-gray-500 font-normal mt-0.5">Beeinflusst Benutzereinstellungen</span>
                        </label>
                        <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 outline-none"
                                name="changeSettings"
                                id="changeSettings">
                            <option value="0" selected>Nein</option>
                            <option value="1">Ja</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-edit text-purple-600 mr-1"></i>
                        Changelog-Inhalt
                        <span class="text-red-600">*</span>
                        <span class="block text-xs text-gray-500 font-normal mt-0.5">Beschreiben Sie die Änderungen ausführlich</span>
                    </label>
                    <textarea class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 outline-none"
                              name="text"
                              rows="15">{{old('text')}}</textarea>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg"
                                id="submitBtn">
                            <i class="fas fa-save"></i>
                            <span>Changelog speichern</span>
                        </button>
                        <a href="{{url('/changelog')}}"
                           class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors duration-200">
                            <i class="fas fa-times"></i>
                            <span>Abbrechen</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
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
