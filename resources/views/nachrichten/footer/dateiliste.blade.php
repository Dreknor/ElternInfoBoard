<div class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-3 py-2">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h6 class="text-sm font-semibold text-white mb-0">
                Downloads ({{count($nachricht->getMedia('files'))}})
            </h6>
        </div>
    </div>

    <!-- File List -->
    <div class="divide-y divide-gray-200">
        @foreach($nachricht->getMedia('files')->sortBy('name') as $media)
            <div class="group hover:bg-blue-50 transition-colors duration-200">
                <div class="flex items-center justify-between px-3 py-2.5">
                    <a href="{{url('/image/'.$media->id)}}"
                       target="_blank"
                       class="flex-1 flex items-center gap-2 text-gray-700 hover:text-blue-600 transition-colors duration-200 min-w-0">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors duration-200">
                            @php
                                $extension = strtolower(pathinfo($media->name, PATHINFO_EXTENSION));
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
                            <i class="fas {{$iconClass}} text-sm"></i>
                        </div>
                        <span class="text-sm font-medium truncate">{{$media->name}}</span>
                    </a>

                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="{{url('/image/'.$media->id)}}"
                           target="_blank"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-200 opacity-0 group-hover:opacity-100"
                           title="Download">
                            <i class="fas fa-download text-xs"></i>
                        </a>

                        @can('edit posts')
                            <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200 opacity-0 group-hover:opacity-100 fileDelete"
                                    data-id="{{$media->id}}"
                                    title="Löschen">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Footer with total size or file count info -->
    <div class="bg-gray-50 px-3 py-2 border-t border-gray-200">
        <p class="text-xs text-gray-500 text-center mb-0">
            <i class="fas fa-info-circle text-blue-500 mr-1"></i>
            Klicken zum Herunterladen
        </p>
    </div>
</div>
