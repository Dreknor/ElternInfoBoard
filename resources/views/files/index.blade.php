@extends('layouts.app')
@section('title') - Downloads @endsection

@section('content')

    <div class="container-fluid">
        <div class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-3 py-2">
                <div class="flex items-center gap-2">
                    <h6 class="text-sm font-semibold text-white mb-0">Datei-Downloads ({{ count($medien ?? []) }})</h6>
                </div>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach(collect($medien ?? [])->sortBy('name') as $medium)
                    <div class="group hover:bg-blue-50 transition-colors duration-200">
                        <div class="flex items-center justify-between px-3 py-2.5">
                            <a href="{{ url('/image/'.$medium->id) }}"
                               target="_blank"
                               class="flex-1 flex items-center gap-2 text-gray-700 hover:text-blue-600 transition-colors duration-200 min-w-0">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors duration-200">
                                    @php
                                        $extension = strtolower(pathinfo($medium->name, PATHINFO_EXTENSION));
                                        $iconClass = match($extension) {
                                            'pdf' => 'fa-file-pdf text-red-500',
                                            'doc', 'docx' => 'fa-file-word text-blue-500',
                                            'xls', 'xlsx' => 'fa-file-excel text-green-500',
                                            'ppt', 'pptx' => 'fa-file-powerpoint text-orange-500',
                                            'zip', 'rar' => 'fa-file-archive text-yellow-600',
                                            'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image text-purple-500',
                                            default => 'fa-file text-gray-500',
                                        };
                                    @endphp
                                    <i class="fas {{ $iconClass }} text-sm"></i>
                                </div>

                                <span class="text-sm font-medium truncate">{{ $medium->name }}</span>
                            </a>

                            <a href="{{ url('/image/'.$medium->id) }}"
                               target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200 opacity-0 group-hover:opacity-100"
                               title="Download">
                                <i class="fas fa-download text-xs"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="bg-gray-50 px-3 py-2 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center mb-0">
                    <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                    Klicken zum Herunterladen
                </p>
            </div>
        </div>
    </div>

@endsection
