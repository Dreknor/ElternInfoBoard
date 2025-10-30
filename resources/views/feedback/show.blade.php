@extends('layouts.app')
@section('title') - Kontakt @endsection

@section('content')
<div class="container-fluid px-4 py-3 space-y-4">
    <!-- Nachricht erstellen Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
            <h5 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                <i class="fas fa-envelope"></i>
                Nachricht erstellen
            </h5>
        </div>

        <div class="p-4">
            <form action="{{url("/feedback")}}" method="post" class="space-y-4"
                  enctype="multipart/form-data">
                @csrf

                <div>
                    <label for="mitarbeiter" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-blue-600 mr-1"></i>
                        Empfänger
                    </label>
                    <select name="mitarbeiter"
                            id="mitarbeiter"
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                        <option value="">Sekretariat</option>
                        @foreach($mitarbeiter->sortBy('FamilieName') as $Mitarbeiter)
                            <option value="{{$Mitarbeiter->id}}" @if($Mitarbeiter->id == $id) selected @endif>
                                {{$Mitarbeiter->familieName}}, {{$Mitarbeiter->vorname}}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="betreff" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-heading text-blue-600 mr-1"></i>
                        Betreff
                    </label>
                    <input class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                           id="betreff"
                           name="betreff"
                           placeholder="Betreff der Nachricht"
                           value="{{old('betreff', 'Nachricht von '.auth()->user()->name)}}">
                </div>

                <div>
                    <label for="text" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left text-blue-600 mr-1"></i>
                        Nachricht
                    </label>
                    <textarea class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none resize-none"
                              id="text"
                              name="text"
                              rows="8"
                              placeholder="Ihre Nachricht...">{{old('text')}}</textarea>
                </div>

                <div>
                    <label for="customFile" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-paperclip text-blue-600 mr-1"></i>
                        Datei anfügen
                    </label>
                    <input type="file"
                           name="files[]"
                           id="customFile"
                           multiple
                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-paper-plane"></i>
                    Feedback senden
                </button>
            </form>
        </div>
    </div>

    <!-- Alte Nachrichten -->
    @can('see mails')
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-4 py-3 border-b border-gray-800">
                <h5 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-history"></i>
                    Alte Nachrichten
                </h5>
            </div>

            <div class="p-4">
                @if($mails->count() > 0)
                    <div class="space-y-3">
                        @foreach($mails as $mail)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition-all duration-200">
                                <!-- Mail Header -->
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-3 pb-3 border-b border-gray-200">
                                    <h6 class="font-semibold text-gray-800 mb-0">
                                        {{$mail->subject}}
                                    </h6>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            {{$mail->created_at->format('d.m.Y H:i')}}
                                        </span>
                                        @can('see mails')
                                            <form action="{{url('/feedback/'.$mail->id)}}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>

                                <!-- Mail Content -->
                                <div class="prose max-w-none text-gray-700 mb-3">
                                    {!! $mail->text !!}
                                </div>

                                <!-- Attachments -->
                                @if($mail->getMedia('files')->count() > 0)
                                    <div class="border-t border-gray-200 pt-3">
                                        <p class="text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-paperclip mr-1"></i>
                                            Anhänge:
                                        </p>
                                        <div class="space-y-1">
                                            @foreach($mail->getMedia('files') as $file)
                                                <a href="{{url('/image/'.$file->id)}}"
                                                   target="_blank"
                                                   class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-blue-50 rounded-lg transition-colors text-sm">
                                                    <i class="fas fa-file-download text-blue-600"></i>
                                                    <span class="text-gray-800">{{$file->name}}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-start gap-3 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                        <p class="text-blue-800 text-sm mb-0">Keine Nachrichten vorhanden</p>
                    </div>
                @endif
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/sortable.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/purify.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/fileinput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/themes/fas/theme.min.js"></script>

    <script>
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
    <script>
        tinymce.init({
            selector: 'textarea#text',
            lang:'de',
            height: 500,
            menubar: false,
        });
    </script>
@endpush

