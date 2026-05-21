@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3 space-y-4">

        <!-- Zurück / Ansehen -->
        <div>
            <a href="{{ route('sites.show', ['site' => $site->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white font-medium rounded-lg transition-colors duration-200 shadow-sm border border-gray-200"
               style="color: var(--color-primary)"
               onmouseover="this.style.backgroundColor='var(--color-primary-light)'"
               onmouseout="this.style.backgroundColor='#ffffff'">
                <i class="fas fa-eye"></i>
                Seite ansehen
            </a>
        </div>

        <!-- Abschnitte bearbeiten -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
                <h3 class="text-xl font-bold mb-0 flex items-center gap-2" style="color: var(--color-widget-header-text)">
                    <i class="fas fa-edit"></i>
                    Seite bearbeiten: {{ $site->name }}
                </h3>
            </div>

            @foreach($site->blocks as $block)
                <div class="p-4 border-b border-gray-100">
                    <!-- Block Header -->
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="font-semibold text-gray-800 mb-0">
                            {{ $block->title ?: '(Kein Titel)' }}
                        </h6>
                        <div class="flex items-center gap-2">
                            @if($block->position > 1)
                                <a href="{{ route('blocks.move.up', ['block' => $block->id]) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-medium rounded-lg transition-colors">
                                    <i class="fas fa-arrow-up"></i>
                                    Nach oben
                                </a>
                            @endif
                            @if($block->position < $site->blocks->count())
                                <a href="{{ route('blocks.move.down', ['block' => $block->id]) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-medium rounded-lg transition-colors">
                                    <i class="fas fa-arrow-down"></i>
                                    Nach unten
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Block Content -->
                    @switch(class_basename($block->block))
                        @case('SiteBlockText')
                            <div class="space-y-3">
                                <form action="{{route('blocks.text.update', ['block' => $block->id])}}" method="post" name="updateBlock.{{$block->id}}" class="space-y-3">
                                    @csrf
                                    @method('put')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                                        <input type="text"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200"
                                               name="title" value="{{$block->title}}"
                                               onfocus="this.style.borderColor='var(--color-primary)'"
                                               onblur="this.style.borderColor='#d1d5db'">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Inhalt</label>
                                        <textarea name="content" id="content_{{ $block->id }}"
                                                  class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg">{{ $block->block->content }}</textarea>
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                                style="background-color: var(--color-widget-primary-from)"
                                                onmouseover="this.style.backgroundColor='var(--color-widget-primary-to)'"
                                                onmouseout="this.style.backgroundColor='var(--color-widget-primary-from)'">
                                            <i class="fas fa-save"></i>
                                            Abschnitt speichern
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200"
                                                form="destroyBlock.{{$block->id}}">
                                            <i class="fas fa-trash"></i>
                                            Abschnitt löschen
                                        </button>
                                    </div>
                                </form>
                                <form method="post" action="{{route('blocks.delete', [$block->id])}}" id="destroyBlock.{{$block->id}}">
                                    @csrf
                                    @method('delete')
                                </form>
                            </div>
                            @break

                        @case('SiteBlockImages')
                            <div class="space-y-4">
                                @if($block->block->getMedia()->count() > 0)
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                        @foreach($block->block->getMedia() as $media)
                                            <div class="bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
                                                <img src="{{ $media->getUrl() }}" alt="{{ $media->name }}" class="w-full h-32 object-cover">
                                                @can('create sites')
                                                    <div class="p-2">
                                                        <form action="{{ route('blocks.media.delete', ['block' => $block->id, 'media' => $media->id]) }}" method="post">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit"
                                                                    class="w-full inline-flex items-center justify-center gap-1 px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded transition-colors">
                                                                <i class="fas fa-trash"></i>
                                                                Bild löschen
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endcan
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <form action="{{ route('blocks.image.store',['block' => $block->id]) }}" method="post" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="block_id" value="{{$block->id}}">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                                        <input type="text" name="title"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200"
                                               value="{{ old('title') }}"
                                               onfocus="this.style.borderColor='var(--color-primary)'"
                                               onblur="this.style.borderColor='#d1d5db'">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Bilder</label>
                                        <input type="file" name="files[]"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg bg-white"
                                               multiple accept="image/png, image/jpeg">
                                    </div>
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                            style="background-color: var(--color-widget-primary-from)"
                                            onmouseover="this.style.backgroundColor='var(--color-widget-primary-to)'"
                                            onmouseout="this.style.backgroundColor='var(--color-widget-primary-from)'">
                                        <i class="fas fa-upload"></i>
                                        Bilder hinzufügen
                                    </button>
                                </form>
                            </div>
                            @break

                        @case('SiteBlockFiles')
                            <div class="space-y-4">
                                @if($block->block->getMedia()->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($block->block->getMedia() as $media)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                                <a href="{{ $media->getUrl() }}" target="_blank" class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900">
                                                    <i class="fas fa-file text-gray-400"></i>
                                                    {{ $media->file_name }}
                                                </a>
                                                @can('create sites')
                                                    <form action="{{ route('blocks.media.delete', ['block' => $block->id, 'media' => $media->id]) }}" method="post">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded transition-colors">
                                                            <i class="fas fa-trash"></i>
                                                            Löschen
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <form action="{{ route('blocks.files.store',['block' => $block->id]) }}" method="post" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="block_id" value="{{$block->id}}">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                                        <input type="text" name="title"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200"
                                               value="{{ old('title') }}"
                                               onfocus="this.style.borderColor='var(--color-primary)'"
                                               onblur="this.style.borderColor='#d1d5db'">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Dateien</label>
                                        <input type="file" name="files[]"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg bg-white"
                                               multiple>
                                    </div>
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                            style="background-color: var(--color-widget-primary-from)"
                                            onmouseover="this.style.backgroundColor='var(--color-widget-primary-to)'"
                                            onmouseout="this.style.backgroundColor='var(--color-widget-primary-from)'">
                                        <i class="fas fa-upload"></i>
                                        Dateien hinzufügen
                                    </button>
                                </form>
                            </div>
                            @break
                    @endswitch
                </div>
            @endforeach

            <!-- Abschnitt hinzufügen -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <h6 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-plus-circle" style="color: var(--color-primary)"></i>
                    Abschnitt hinzufügen
                </h6>
                <form action="{{ route('blocks.store') }}" method="post" class="space-y-3">
                    @csrf
                    <input type="hidden" name="site_id" value="{{ $site->id }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                            <input type="text" name="title" id="title"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200 @error('title') border-red-500 @enderror"
                                   value="{{ old('title') }}"
                                   onfocus="this.style.borderColor='var(--color-primary)'"
                                   onblur="this.style.borderColor='#d1d5db'">
                            @error('title')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="block_type" class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                            <select name="block_type" id="type"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200"
                                    onfocus="this.style.borderColor='var(--color-primary)'"
                                    onblur="this.style.borderColor='#d1d5db'">
                                <option value="text">Text</option>
                                <option value="image">Bild</option>
                                <option value="files">Dateien</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                            style="background-color: var(--color-primary)"
                            onmouseover="this.style.backgroundColor='var(--color-primary-dark)'"
                            onmouseout="this.style.backgroundColor='var(--color-primary)'">
                        <i class="fas fa-plus"></i>
                        Abschnitt hinzufügen
                    </button>
                </form>
            </div>
        </div>

        <!-- Seite löschen -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-warning-from), var(--color-widget-warning-to)); border-color: var(--color-widget-warning-border)">
                <h3 class="text-xl font-bold mb-0 flex items-center gap-2" style="color: var(--color-widget-header-text)">
                    <i class="fas fa-exclamation-triangle"></i>
                    Seite löschen
                </h3>
            </div>
            <div class="p-4">
                <p class="text-gray-700 mb-4">
                    <strong>ACHTUNG!</strong> Wenn Sie die Seite löschen, werden auch alle Abschnitte und Medien gelöscht.
                </p>
                <form action="{{ route('sites.destroy', ['site' => $site->id]) }}" method="post">
                    @csrf
                    @method('delete')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200"
                            onclick="return confirm('Seite wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')">
                        <i class="fas fa-trash"></i>
                        Seite löschen
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea[name="content"]',
            lang:'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink lists link charmap',
                'searchreplace visualblocks code',
                'insertdatetime table paste code wordcount',
                'media',
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
            toolbar: 'undo redo | formatselect | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link | media',
            @if(auth()->user()->can('use scriptTag'))
            extended_valid_elements : "script[src|async|defer|type|charset]",
            @endif
        });</script>

@endpush
