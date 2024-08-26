@extends('layouts.app')

@section('content')
    @cache('site'.$site->id, 10*60*60)
        <div class="container-fluid">
        <div class="row">
            <div class="col-auto">
                <a href="{{ route('sites.index') }}" class="btn btn-primary">Zurück zur Übersicht</a>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-12 col-md-9">
                                <h5>
                                    {{ $site->name }} @if(!$site->is_active) <span class="text-danger">(Unveröffentlicht)</span> @endif
                                </h5>
                            </div>
                            @can('create sites')
                                <div class="col">
                                    <div class="pull-right">
                                        <a href="{{ route('sites.edit', $site->id) }}" class="btn btn-xs btn-primary">Bearbeiten</a>
                                    </div>
                                </div>

                                @if(!$site->is_active)
                                    <div class="col">
                                        <div class="pull-right">
                                           <a href="{{ route('sites.activate', $site->id) }}" class="btn btn-xs btn-success">Veröffentlichen</a>
                                        </div>
                                    </div>
                                @endif
                            @endcan


                    </div>
                    @foreach($site->blocks as $block)
                        <div class="card-body">
                            @if($block->title)
                                <h6>{{ $block->title }}</h6>
                            @endif
                            @switch(class_basename($block->block))
                                @case('SiteBlockText')
                                        {!!  $block->block->content !!}
                                    @break
                                @case('SiteBlockImages')
                                    <div class="card-columns">
                                        @foreach($block->block->getMedia() as $media)
                                           <div class="card">
                                                <div class="card-body">
                                                    <a href="{{url('/image/'.$media->id)}}" target="_blank">
                                                        <img class="d-block mx-auto" src="{{url('/image/'.$media->id)}}" style="max-height: 480px" >
                                                    </a>
                                                </div>
                                           </div>

                                        @endforeach
                                    </div>
                                    @break
                                @case('SiteBlockFiles')
                                    <div class="card-deck">
                                        @foreach($block->block->getMedia() as $media)
                                            <a href="{{ $media->getUrl() }}" target="_blank">
                                                <div class="card bg-light-gray">
                                                    <div class="card-body">
                                                        @switch($media->mime_type)
                                                            @case('application/pdf')
                                                                <i class="far fa-file-pdf fa-3x"></i>
                                                                {{ $media->name }}
                                                                @break
                                                            @case('application/msword')
                                                            @case('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                                                                <i class="far fa-file-word fa-3x"></i>
                                                                {{ $media->name }}
                                                                @break
                                                            @case('application/vnd.ms-excel')
                                                            @case('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                                                                <i class="far fa-file-excel fa-3x"></i>
                                                                {{ $media->name }}
                                                                @break
                                                            @case('application/vnd.ms-powerpoint')
                                                            @case('application/vnd.openxmlformats-officedocument.presentationml.presentation')
                                                                <i class="far fa-file-powerpoint fa-3x"></i>
                                                                {{ $media->name }}
                                                                @break
                                                            @default
                                                                <i class="far fa-file fa-3x"></i>
                                                                {{ $media->name }}
                                                        @endswitch

                                                    </div>

                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                    @break
                                @default
                                    <p>Unbekannter Block-Typ</p>
                            @endswitch
                        </div>
                    @endforeach
                </div>
                <div class="card-footer mt-4">
                    <div class="row">
                        <div class="col-auto">
                            zuletzt bearbeitet am {{ $site->updated_at->format('d.m.Y H:i') }} von {{ $site->user?->name }}
                        </div>
                    </div>
                </div>
        </div>
    </div>


    @endcache
@endsection
