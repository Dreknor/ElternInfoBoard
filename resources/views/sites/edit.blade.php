@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-auto">
                <a href="{{ route('sites.show', ['site' => $site->id]) }}" class="btn btn-primary">Ansehen</a>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-auto">
                        <h3>Seite bearbeiten</h3>
                    </div>
                </div>
            </div>
            @foreach($site->blocks as $block)
                <div class="card-body bg-light-gray">
                    <div class="row">
                        <div class="col-auto">
                            <h6>
                                {{ $block->title }}
                            </h6>
                        </div>
                        <div class="col">
                            <div class="pull-right">
                                @if($block->position > 1)
                                    <a href="{{ route('blocks.move.up', ['block' => $block->id]) }}" class="btn btn-xs btn-primary">Nach oben</a>
                                @endif
                                @if($block->position < $site->blocks->count())
                                    <a href="{{ route('blocks.move.down', ['block' => $block->id]) }}" class="btn btn-xs btn-primary">Nach unten</a>
                                @endif

                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-body border-top">
                    @switch(class_basename($block->block))
                            @case('SiteBlockText')
                                <form action="{{route('blocks.text.update', ['block' => $block->id])}}" method="post" name="updateBlock.{{$block->id}}">
                                    @csrf
                                    @method('put')
                                    <div class="row">
                                        <div class="col-12">
                                            <label for="blocktitle">Titel</label>
                                            <input type="text" class="form-control" name="title" value="{{$block->title}}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <label for="content">Inhalt</label>
                                            <textarea name="content" id="content" class="form-control">
                                                {{ $block->block->content }}
                                            </textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 col-md-6">
                                            <button type="submit" class="btn btn-primary">Abschnitt speichern</button>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <button type="submit" class="btn btn-danger pull-right" form="destroyBlock.{{$block->id}}">Abschnitt löschen</button>
                                        </div>
                                    </div>
                                </form>
                                <form method="post" action="{{route('blocks.delete', [$block->id])}}" id="destroyBlock.{{$block->id}}" class="form-inline">
                                    @csrf
                                    @method('delete')

                                </form>

                                @break
                            @case('SiteBlockImages')
                            <div class="card-columns">
                            @foreach($block->block->getMedia() as $media)
                               <div class="card">
                                    <div class="card-body">
                                      {{ $media }}
                                    </div>
                                   @can('create sites')
                                        <div class="card-footer">
                                             <form action="{{ route('blocks.media.delete', ['block' => $block->id, 'media' => $media->id]) }}" method="post">
                                                  @csrf
                                                  @method('delete')
                                                  <button type="submit" class="btn btn-danger btn-link">Bild löschen</button>
                                             </form>
                                        </div>
                                    @endcan
                               </div>

                            @endforeach
                            </div>

                                <form action="{{ route('blocks.image.store',['block' => $block->id]) }}" method="post" class="form form-horizontal" enctype="multipart/form-data" id="blockImage.{{$block->id}}">
                                    @csrf
                                    <input type="hidden" name="block_id" value="{{$block->id}}">

                                    <div class="row">
                                        <div class="col-12">
                                            <label for="title">Titel</label>
                                            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <label for="files" >Bilder</label>
                                            <input type="file" name="files[]" id="files" class="form-control customFile" multiple accept="image/png, image/jpeg" >
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Bilder hinzufügen</button>
                                        </div>
                                    </div>
                                </form>
                                @break
                            @case('SiteBlockFiles')
                            <div class="card-columns">
                                @foreach($block->block->getMedia() as $media)
                                     <div class="card">
                                        <div class="card-body">
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                            {{ $media->file_name }}
                                            </a>
                                        </div>
                                        @can('create sites')
                                            <div class="card-footer">
                                                <form action="{{ route('blocks.media.delete', ['block' => $block->id, 'media' => $media->id]) }}" method="post">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="btn btn-danger btn-link">Datei löschen</button>
                                                </form>
                                            </div>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>

                        <form action="{{ route('blocks.files.store',['block' => $block->id]) }}" method="post" class="form form-horizontal" enctype="multipart/form-data" id="blockFile.{{$block->id}}">
                            @csrf
                            <input type="hidden" name="block_id" value="{{$block->id}}">

                            <div class="row">
                                <div class="col-12">
                                    <label for="title">Titel</label>
                                    <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label for="files" >Dateien</label>
                                    <input type="file" name="files[]" id="files" class="form-control customFile" multiple >
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Dateien hinzufügen</button>
                                </div>
                            </div>
                        </form>
                        @break
                    @endswitch
                </div>
            @endforeach
            <div class="card-footer border-top">
                <h6>
                    Abschnitt hinzufügen
                </h6>
                <form action="{{ route('blocks.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="site_id" value="{{ $site->id }}">
                    <div class="form-group row">
                        <div class="col-sm-12 col-md-6">
                            <label for="title">Titel</label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}">
                            @error('title')
                            <span class="help-block
                            @error('title')
                                has-error
                            @enderror
                            ">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="block_type">Typ</label>
                            <select name="block_type" id="type" class="form-control">
                                <option value="text">Text</option>
                                <option value="image">Bild</option>
                                <option value="files">Dateien</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <button type="submit" class="btn btn-primary">Abschnitt hinzufügen</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-danger">
                <h3>Seite löschen</h3>
            </div>
            <div class="card-body">
                <p>
                    ACHTUNG! Wenn Sie die Seite löschen, werden auch alle Abschnitte und Medien gelöscht.
                </p>
                <form action="{{ route('sites.destroy', ['site' => $site->id]) }}" method="post">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn btn-danger">Seite löschen</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('css')

@endpush

@push('js')


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
                'contextmenu media textcolor',
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
            contextmenu: " link image inserttable | cell row column deletetable",
            @if(auth()->user()->can('use scriptTag'))
            extended_valid_elements : "script[src|async|defer|type|charset]",
            @endif

        });</script>





@endpush
