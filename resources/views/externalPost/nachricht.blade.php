@if((count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0) and $nachricht->type == 'image')
    <div class="rounded-xl shadow-lg overflow-hidden mb-6" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex-1"></div>
            @if(request()->segment(1)!="kiosk" and auth()->check() and (auth()->user()->can('edit posts') or auth()->id() == $nachricht->author ))
                <div class="flex items-center gap-2">

                    @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)))
                        <a href="{{url('/posts/edit/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-md"
                           id="editTextBtn" data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                            <i class="far fa-edit"></i>
                        </a>
                        <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md"
                           data-toggle="tooltip" data-placement="top" title="Nachricht nach oben schieben">
                            <i class="fas fa-redo"></i>
                        </a>
                    @else
                        <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md"
                           data-toggle="tooltip" data-placement="top" title="Nachricht kopieren">
                            <i class="far fa-clone"></i>
                        </a>
                    @endif
                    @if($nachricht->released == 0)
                        <a href="{{url('/posts/release/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md"
                           data-toggle="tooltip" data-placement="top" title="Nachricht veröffentlichen">
                            <i class="far fa-eye"></i>
                        </a>
                    @endif
                    @if($nachricht->released == 1 and !$nachricht->is_archived)
                        <form action="{{url('/posts/archiv/'.$nachricht->id)}}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-md"
                               data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                <i class="fas fa-archive"></i>
                            </button>
                        </form>
                    @endif

                </div>
            @endif
        </div>

        <div class="px-4 pb-4">
            <div id="carousel_post_{{$nachricht->id}}" class="carousel slide mx-auto" data-ride="carousel">
                <div class="carousel-inner rounded-lg overflow-hidden">
                    @foreach($nachricht->getMedia('images')->sortBy('name') as $media)
                        <div class="carousel-item text-center @if($loop->first) active @endif">
                            <a href="{{url('/image/'.$media->id)}}" target="_blank" class="block">
                                <img class="mx-auto max-h-[600px] w-auto" loading="lazy" decoding="async" src="{{url('/image/'.$media->id)}}" alt="{{$media->name ?? 'Bild'}}">
                            </a>
                        </div>
                    @endforeach

                </div>

                @if(count($nachricht->getMedia('images'))>1)
                    <a class="carousel-control-prev" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon bg-blue-600 rounded-full p-2" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="next">
                        <span class="carousel-control-next-icon bg-blue-600 rounded-full p-2" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                @endif
            </div>

        </div>
    </div>
@else
    @php
        $isUnreleased = $nachricht->released == 0;
        $cardBorderStyle = $isUnreleased ? 'background-color: var(--color-card-bg); border: 1px solid var(--color-card-border); outline: 2px solid #f59e0b;' : 'background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);';
        $headerStyle = $isUnreleased
            ? 'background: linear-gradient(to right, #fbbf24, #f59e0b); border-color: #d97706;'
            : 'background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border);';
        $titleColor = $isUnreleased ? 'color: #1f2937;' : 'color: var(--color-widget-header-text);';
        $metaColor = $isUnreleased ? 'color: #374151;' : 'color: var(--color-widget-header-text); opacity: 0.85;';
    @endphp

    <div class="nachricht {{$nachricht->type}} rounded-xl shadow-lg overflow-hidden mb-6 transition-shadow duration-300 hover:shadow-xl"
         id="{{$nachricht->id}}" style="{{ $cardBorderStyle }}"
         @if($nachricht->is_archived) x-data="{ showArchived: false }" @endif>
        @if(count($nachricht->getMedia('header'))>0)
            <div class="relative h-56 overflow-hidden">
                <img class="w-full h-full object-cover object-center" loading="lazy" decoding="async" src="{{url('/image/'.$nachricht->getMedia('header')->first()->id)}}"
                     style="object-position: 0 70%;" alt="Header-Bild">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
        @endif

        <div class="px-6 py-4 border-b" style="{{ $headerStyle }}">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <h5 class="text-lg font-semibold mb-1 flex items-center gap-2" style="{{ $titleColor }}">
                        @if($nachricht->sticky)
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-300 text-yellow-800 rounded-full">
                                <i class="fas fa-thumbtack text-xs"></i>
                            </span>
                        @endif
                        <span class="break-words">{{$nachricht->header}}</span>
                        @if($nachricht->released == 0)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-black/10">unveröffentlicht</span>
                        @endif
                    </h5>

                    <div class="flex flex-wrap text-sm gap-4" style="{{ $metaColor }}">
                        <div>aktualisiert: {{$nachricht->updated_at->isoFormat('DD. MMMM YYYY HH:mm')}}</div>
                        <div>Archiv ab: {{$nachricht->archiv_ab?->isoFormat('DD. MMMM YYYY')}}</div>
                        <div class="ml-auto">Autor: {{$nachricht->autor?->name}}</div>
                    </div>
                </div>

                @if(auth()->check() and (auth()->user()->can('edit posts') or auth()->id() == $nachricht->author ))
                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                        <a href="{{url('/posts/edit/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-md"
                           id="editTextBtn" data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                            <i class="far fa-edit"></i>
                        </a>
                        @if($nachricht->released == 0)
                            <a href="{{url('/posts/release/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md"
                               data-toggle="tooltip" data-placement="top" title="Nachricht veröffentlichen">
                                <i class="far fa-eye"></i>
                            </a>
                        @endif
                        @if($nachricht->released == 1 and !$nachricht->is_archived)
                            <form action="{{url('/posts/archiv/'.$nachricht->id)}}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-md"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                    <i class="fas fa-archive"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            @if($nachricht->is_archived)
                <button @click="showArchived = !showArchived"
                        class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 rounded-md transition-colors duration-200"
                        style="background-color: var(--color-primary); color: var(--color-widget-header-text);"
                        onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary-dark')"
                        onmouseout="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary')">
                    <i class="fa mr-2" :class="showArchived ? 'fa-eye-slash' : 'fa-eye'"></i>
                    <span x-text="showArchived ? 'Text ausblenden' : 'Text anzeigen'"></span>
                </button>
            @endif
        </div>

        <div x-show="@if($nachricht->is_archived) showArchived @else true @endif"
             @if($nachricht->is_archived)
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             style="display: none;"
             @endif
             id="Collapse{{$nachricht->id}}"
             class="p-6">
            @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 prose max-w-none leading-relaxed" style="color: var(--color-text-primary);">
                        {!! $nachricht->news !!}
                    </div>
                    <div class="md:col-span-1 space-y-4">
                        @if(count($nachricht->getMedia('images'))>0)
                            @include('nachrichten.footer.bilder')
                        @endif

                        @if(count($nachricht->getMedia('files'))>0)
                            @include('nachrichten.footer.dateiliste')
                        @endif
                    </div>
                </div>
            @else
                <div class="prose max-w-none leading-relaxed" style="color: var(--color-text-primary);">
                    {!! $nachricht->news !!}
                </div>
            @endif
        </div>

        @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'email')
            @if(!$nachricht->is_archived and $nachricht->rueckmeldung->pflicht == 1)
                <div class="px-6 pb-4">
                    <div class="flex items-center gap-2">
                        @for($x=1; $x <= $nachricht->userRueckmeldung->count(); $x++)
                            <i class="fas fa-user-alt text-green-600" title="{{$x}}"></i>
                        @endfor
                        @for($x=1; $x <= ((round($nachricht->users->where('sorg2', '!=', null)->unique('email')->count()/2)) + $nachricht->users->where('sorg2', 0)->unique('email')->count())-$nachricht->userRueckmeldung->count(); $x++)
                            <i class="fas fa-user-alt text-red-600" title="{{$x}}"></i>
                        @endfor
                    </div>
                </div>
            @endif
            @include('nachrichten.footer.rueckmeldung')
            @can('view rueckmeldungen')
                <div class="border-t px-6 py-3" style="border-color: var(--color-card-border);">
                    <button class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors duration-200 btnShowRueckmeldungen"
                            style="background-color: var(--color-primary); color: var(--color-widget-header-text);"
                            onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary-dark')"
                            onmouseout="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary')"
                            data-toggle="collapse" data-target="#{{$nachricht->id}}_rueckmeldungen">
                        <i class="fa fa-eye mr-2"></i>
                        {{$nachricht->userRueckmeldung->count()}} Rückmeldungen anzeigen
                    </button>
                    <div id="{{$nachricht->id}}_rueckmeldungen" class="collapse mt-3">
                        @include('nachrichten.footer.eingegangeneRueckmeldung')
                    </div>
                </div>
            @endcan
        @endif
        @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'bild' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
            @include('nachrichten.footer.imageRueckmeldung')
        @endif

        {{-- Beitrag melden --}}
        <div class="border-t" style="border-color: var(--color-card-border);">
            @include('nachrichten.footer.report')
        </div>
    </div>
@endif
