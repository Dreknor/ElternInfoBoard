<div class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-3 py-2">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <h6 class="text-sm font-semibold text-white mb-0">
                Bilder ({{count($nachricht->getMedia('images'))}})
            </h6>
        </div>
    </div>

    <!-- Carousel -->
    <div class="relative bg-gray-50">
        <div id="carousel_post_{{$nachricht->id}}" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
                @foreach($nachricht->getMedia('images')->sortBy('name') as $media)
                    <div class="carousel-item @if($loop->first) active @endif">
                        <a href="{{url('/image/'.$media->id)}}" target="_blank" class="block group">
                            <div class="relative" style="height: 240px;">
                                <img class="absolute inset-0 w-full h-full object-contain p-2 transition-transform duration-200 group-hover:scale-105"
                                     src="{{url('/image/'.$media->id)}}"
                                     alt="Bild {{$loop->iteration}}">
                                <!-- Hover Overlay -->
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-white/90 rounded-full p-3 shadow-lg">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            @if($nachricht->rueckmeldung?->type == 'bild')
                                <div class="px-3 py-2 bg-white border-t border-gray-200">
                                    <p class="text-xs text-gray-600 text-center truncate">{{$media->name}}</p>
                                </div>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- Navigation Arrows -->
            @if(count($nachricht->getMedia('images'))>1)
                <a class="carousel-control-prev" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="prev">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-600 shadow-lg hover:bg-purple-700 transition-colors duration-200" aria-hidden="true">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="next">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-600 shadow-lg hover:bg-purple-700 transition-colors duration-200" aria-hidden="true">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </span>
                    <span class="sr-only">Next</span>
                </a>

                <!-- Indicators -->
                <ol class="carousel-indicators" style="bottom: -30px;">
                    @foreach($nachricht->getMedia('images') as $media)
                        <li data-target="#carousel_post_{{$nachricht->id}}"
                            data-slide-to="{{$loop->index}}"
                            class="@if($loop->first) active @endif bg-purple-600 hover:bg-purple-700 transition-colors duration-200"
                            style="width: 8px; height: 8px; border-radius: 50%; margin: 0 4px;"></li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>

    <!-- Footer -->
    @if(count($nachricht->getMedia('images'))>1)
        <div class="bg-white px-3 py-2 border-t border-gray-200" style="margin-top: 30px;">
            <p class="text-xs text-gray-500 text-center mb-0">
                <i class="fas fa-arrows-alt-h text-purple-500 mr-1"></i>
                Wischen oder Pfeile nutzen zum Durchblättern
            </p>
        </div>
    @endif
</div>


