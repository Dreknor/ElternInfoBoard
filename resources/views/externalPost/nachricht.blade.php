@if((count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0) and $nachricht->type == 'image')
    <div class="bg-white shadow-md rounded-xl overflow-hidden mb-6">
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
                        <a href="{{url('/posts/archiv/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-md"
                           data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                            <i class="fas fa-archive"></i>
                        </a>
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
                                <img class="mx-auto max-h-[600px] w-auto" src="{{url('/image/'.$media->id)}}" alt="{{$media->name ?? 'Bild'}}">
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
        $cardClass = 'nachricht '.$nachricht->type.' bg-white rounded-xl shadow-md overflow-hidden mb-6';
        if($nachricht->released == 0) { $cardClass .= ' ring-2 ring-blue-500'; }
        $headerClass = $nachricht->released == 0 ? 'px-6 py-4 border-b bg-blue-600 text-white border-blue-700' : 'px-6 py-4 border-b bg-gray-50';
    @endphp

    <div class="{{ $cardClass }}" id="{{$nachricht->id}}">
        @if(count($nachricht->getMedia('header'))>0)
            <div class="relative h-56 overflow-hidden">
                <img class="w-full h-full object-cover object-center" src="{{url('/image/'.$nachricht->getMedia('header')->first()->id)}}"
                     style="object-position: 0 70%;" alt="Header-Bild">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
        @endif

        <div class="{{ $headerClass }}">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <h5 class="text-lg font-semibold mb-1 flex items-center gap-2">
                        @if($nachricht->sticky)
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-300 text-yellow-800 rounded-full">
                                <i class="fas fa-thumbtack text-xs"></i>
                            </span>
                        @endif
                        <span class="break-words">{{$nachricht->header}}</span>
                        @if($nachricht->released == 0)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white/20 text-white">unveröffentlicht</span>
                        @endif
                    </h5>

                    <div class="flex flex-wrap text-sm text-gray-600 gap-4">
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
                            <a href="{{url('/posts/archiv/'.$nachricht->id)}}" class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-md"
                               data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                <i class="fas fa-archive"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            @if($nachricht->is_archived)
                <button class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 border border-blue-500 text-blue-700 rounded-md btnShow" data-toggle="collapse"
                        data-target="#Collapse{{$nachricht->id}}">
                    <i class="fa fa-eye mr-2"></i>
                    Text anzeigen
                </button>
            @endif
        </div>

        <div class="p-6 @if($nachricht->is_archived) collapse @endif" id="Collapse{{$nachricht->id}}">
            @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 prose max-w-none text-gray-700">
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
                <div class="prose max-w-none text-gray-700">
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
                <button class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 border border-blue-500 text-blue-700 rounded-md btnShowRueckmeldungen" data-toggle="collapse"
                        data-target="#{{$nachricht->id}}_rueckmeldungen">
                    <i class="fa fa-eye mr-2"></i>
                    {{$nachricht->userRueckmeldung->count()}} Rückmeldungen anzeigen
                </button>
                <div id="{{$nachricht->id}}_rueckmeldungen" class="collapse">
                    @include('nachrichten.footer.eingegangeneRueckmeldung')
                </div>

            @endcan
        @endif
        @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'bild' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
            @include('nachrichten.footer.imageRueckmeldung')
        @endif
    </div>
@endif
