@extends('layouts.app')

@section('content')
    @cache('site'.$site->id, 60*24*7 )
        <div class="container-fluid px-4 py-3">
            <!-- Zurück Button -->
            <div class="mb-4">
                <a href="{{ route('sites.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left"></i>
                    Zurück zur Übersicht
                </a>
            </div>

            <!-- Seiten-Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="px-4 py-3 border-b"
                     style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h5 class="text-xl font-bold mb-0" style="color: var(--color-widget-header-text)">
                            {{ $site->name }}
                            @if(!$site->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-red-100 text-red-700 text-sm font-medium rounded-full ml-2">
                                    <i class="fas fa-eye-slash"></i>
                                    Unveröffentlicht
                                </span>
                            @endif
                        </h5>

                        <div class="flex items-center gap-2">
                            @can('create sites')
                                <a href="{{ route('sites.edit', $site->id) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-white font-medium rounded-lg transition-colors duration-200"
                                   style="color: var(--color-primary)"
                                   onmouseover="this.style.backgroundColor='var(--color-primary-light)'"
                                   onmouseout="this.style.backgroundColor='#ffffff'">
                                    <i class="fas fa-edit"></i>
                                    <span class="hidden md:inline">Bearbeiten</span>
                                </a>

                                @if(!$site->is_active)
                                    <a href="{{ route('sites.activate', $site->id) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                       style="background-color: var(--color-widget-success-from)"
                                       onmouseover="this.style.backgroundColor='var(--color-widget-success-to)'"
                                       onmouseout="this.style.backgroundColor='var(--color-widget-success-from)'">
                                        <i class="fas fa-eye"></i>
                                        <span class="hidden md:inline">Veröffentlichen</span>
                                    </a>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>

                <!-- Blocks -->
                @foreach($site->blocks as $block)
                    <div class="p-4 border-b border-gray-200 last:border-b-0">
                        @if($block->title)
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">{{ $block->title }}</h3>
                        @endif

                        @switch(class_basename($block->block))
                            @case('SiteBlockText')
                                <div class="prose max-w-none text-gray-700">
                                    {!!  $block->block->content !!}
                                </div>
                                @break

                            @case('SiteBlockImages')
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($block->block->getMedia() as $media)
                                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200">
                                            <a href="{{url('/image/'.$media->id)}}" target="_blank" class="block">
                                                <img class="w-full h-auto object-cover"
                                                     src="{{url('/image/'.$media->id)}}"
                                                     alt="Bild"
                                                     style="max-height: 480px;">
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                @break

                            @case('SiteBlockFiles')
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($block->block->getMedia() as $media)
                                        <a href="{{ $media->getUrl() }}" target="_blank"
                                           class="block border border-gray-200 rounded-lg p-4 transition-all duration-200"
                                           onmouseover="this.style.borderColor='var(--color-primary)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'"
                                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow=''">
                                            <div class="flex items-center gap-3">
                                                @switch($media->mime_type)
                                                    @case('application/pdf')
                                                        <i class="far fa-file-pdf fa-3x text-red-600"></i>
                                                        @break
                                                    @case('application/msword')
                                                    @case('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                                                        <i class="far fa-file-word fa-3x text-blue-600"></i>
                                                        @break
                                                    @case('application/vnd.ms-excel')
                                                    @case('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                                                        <i class="far fa-file-excel fa-3x text-green-600"></i>
                                                        @break
                                                    @case('application/vnd.ms-powerpoint')
                                                    @case('application/vnd.openxmlformats-officedocument.presentationml.presentation')
                                                        <i class="far fa-file-powerpoint fa-3x text-orange-600"></i>
                                                        @break
                                                    @default
                                                        <i class="far fa-file fa-3x text-gray-600"></i>
                                                @endswitch
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-800 truncate mb-0">
                                                        {{ $media->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                                @break

                            @default
                                <div class="flex items-start gap-3 p-3 bg-amber-50 border-l-4 border-amber-500 rounded">
                                    <i class="fas fa-exclamation-triangle text-amber-600 mt-1"></i>
                                    <p class="text-amber-800 text-sm mb-0">Unbekannter Block-Typ</p>
                                </div>
                        @endswitch
                    </div>
                @endforeach

                <!-- Footer -->
                <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
                    <p class="text-sm text-gray-600 mb-0">
                        <i class="fas fa-clock mr-1"></i>
                        Zuletzt bearbeitet am {{ $site->updated_at->format('d.m.Y H:i') }} von {{ $site->user?->name }}
                    </p>
                </div>
            </div>
        </div>
    @endcache
@endsection
