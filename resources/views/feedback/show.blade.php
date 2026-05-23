@extends('layouts.app')
@section('title') - Kontakt @endsection

@section('content')
<div class="container-fluid px-4 py-3 space-y-4">
    <!-- Nachricht erstellen Card -->
    <div class="rounded-lg shadow-lg overflow-hidden" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border)">
        <div class="px-4 py-3 border-b"
             style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
            <h5 class="text-xl font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                <i class="fas fa-envelope"></i>
                Nachricht erstellen
            </h5>
        </div>

        <div class="p-4">
            <form action="{{url("/feedback")}}" method="post" class="space-y-4"
                  enctype="multipart/form-data">
                @csrf

                <div>
                    <label for="mitarbeiter" class="block text-sm font-medium mb-2" style="color: var(--color-text-secondary)">
                        <i class="fas fa-user mr-1" style="color: var(--color-widget-primary-from)"></i>
                        Empfänger
                    </label>
                    <select name="mitarbeiter"
                            id="mitarbeiter"
                            class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                            style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">
                        <option value="">Sekretariat</option>
                        @foreach($mitarbeiter->sortBy('FamilieName') as $Mitarbeiter)
                            <option value="{{$Mitarbeiter->id}}" @if($Mitarbeiter->id == $id) selected @endif>
                                {{$Mitarbeiter->familieName}}, {{$Mitarbeiter->vorname}}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="betreff" class="block text-sm font-medium mb-2" style="color: var(--color-text-secondary)">
                        <i class="fas fa-heading mr-1" style="color: var(--color-widget-primary-from)"></i>
                        Betreff
                    </label>
                    <input class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                           style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)"
                           id="betreff"
                           name="betreff"
                           placeholder="Betreff der Nachricht"
                           value="{{old('betreff', 'Nachricht von '.auth()->user()->name)}}">
                </div>

                <div>
                    <label for="text" class="block text-sm font-medium mb-2" style="color: var(--color-text-secondary)">
                        <i class="fas fa-align-left mr-1" style="color: var(--color-widget-primary-from)"></i>
                        Nachricht
                    </label>
                    <textarea class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none resize-none"
                              style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)"
                              id="text"
                              name="text"
                              rows="8"
                              placeholder="Ihre Nachricht...">{{old('text')}}</textarea>
                </div>

                <div>
                    <label for="customFile" class="block text-sm font-medium mb-2" style="color: var(--color-text-secondary)">
                        <i class="fas fa-paperclip mr-1" style="color: var(--color-widget-primary-from)"></i>
                        Datei anfügen
                    </label>
                    <input type="file"
                           name="files[]"
                           id="customFile"
                           multiple
                           class="w-full px-4 py-2 border-2 rounded-lg transition-all duration-200 outline-none"
                           style="border-color: var(--color-input-border); background-color: var(--color-input-bg); color: var(--color-text-primary)">
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg"
                            style="background-color: var(--color-widget-primary-from)">
                        <i class="fas fa-paper-plane"></i>
                        <span>Feedback senden</span>
                    </button>
                    <button type="reset"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3 font-semibold rounded-lg transition-colors duration-200"
                            style="background-color: var(--color-surface-subtle); color: var(--color-text-secondary)">
                        <i class="fas fa-redo"></i>
                        <span class="hidden sm:inline">Zurücksetzen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alte Nachrichten -->
    @can('see mails')
        <div class="rounded-lg shadow-lg overflow-hidden" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border)">
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
                <h5 class="text-xl font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                    <i class="fas fa-history"></i>
                    Alte Nachrichten
                </h5>
            </div>

            <div class="p-4">
                @if($mails->count() > 0)
                    <div class="space-y-3">
                        @foreach($mails as $mail)
                            <div class="border rounded-lg p-4 hover:shadow-md transition-all duration-200"
                                 style="border-color: var(--color-card-border); transition: border-color 0.2s"
                                 onmouseover="this.style.borderColor='var(--color-widget-primary-from)'"
                                 onmouseout="this.style.borderColor='var(--color-card-border)'">
                                <!-- Mail Header -->
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-3 pb-3 border-b" style="border-color: var(--color-card-border)">
                                    <h6 class="font-semibold mb-0" style="color: var(--color-text-primary)">
                                        {{$mail->subject}}
                                    </h6>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm" style="color: var(--color-text-muted)">
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
                                <div class="prose max-w-none mb-3" style="color: var(--color-text-secondary)">
                                    {!! $mail->text !!}
                                </div>

                                <!-- Attachments -->
                                @if($mail->getMedia('files')->count() > 0)
                                    <div class="border-t pt-3" style="border-color: var(--color-card-border)">
                                        <p class="text-sm font-medium mb-2" style="color: var(--color-text-secondary)">
                                            <i class="fas fa-paperclip mr-1"></i>
                                            Anhänge:
                                        </p>
                                        <div class="space-y-1">
                                            @foreach($mail->getMedia('files') as $file)
                                                <a href="{{url('/image/'.$file->id)}}"
                                                   target="_blank"
                                                   class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors text-sm"
                                                   style="background-color: var(--color-widget-body-bg)">
                                                    <i class="fas fa-file-download" style="color: var(--color-widget-primary-from)"></i>
                                                    <span style="color: var(--color-text-primary)">{{$file->name}}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-start gap-3 p-3 border-l-4 rounded"
                         style="background-color: var(--color-widget-body-bg); border-color: var(--color-widget-primary-from)">
                        <i class="fas fa-info-circle mt-1" style="color: var(--color-widget-primary-from)"></i>
                        <p class="text-sm mb-0" style="color: var(--color-widget-primary-border)">Keine Nachrichten vorhanden</p>
                    </div>
                @endif
            </div>
        </div>
    @endcan
</div>
@endsection



@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

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

