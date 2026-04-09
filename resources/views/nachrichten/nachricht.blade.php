@if((count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0) and $nachricht->type == 'image')
        <!-- Image Gallery Type -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6 @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach">
            @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author))
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-2 border-b border-gray-200">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex gap-2">
                            @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'abfrage')
                                <a href="{{url('rueckmeldungen/'.$nachricht->rueckmeldung->id."/download")}}"
                                   title="Download"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fa fa-download mr-1"></i>
                                </a>
                            @endif
                            @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)))
                                <a href="{{url('/posts/edit/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                                    <i class="far fa-edit"></i>
                                </a>
                                <a href="{{url('/posts/touch/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht nach oben schieben">
                                    <i class="fas fa-redo"></i>
                                </a>
                            @else
                                <a href="{{url('/posts/touch/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht kopieren">
                                    <i class="far fa-clone"></i>
                                </a>
                            @endif
                            @if($nachricht->released == 0)
                                <a href="{{url('/posts/release/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht veröffentlichen">
                                    <i class="far fa-eye"></i>
                                </a>
                            @endif
                            @if($nachricht->released == 1 and !$nachricht->is_archived)
                                
                                <form action="{{url('/posts/archiv/'.$nachricht->id)}}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                       class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                       data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                </form>
                            @endif
                            @if(auth()->user()->can('make sticky'))
                                <a href="{{url('/posts/stick/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 @if($nachricht->sticky) bg-green-100 text-green-700 hover:bg-green-200 @else bg-purple-600 hover:bg-purple-700 text-white @endif text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht anheften">
                                    <i class="fas fa-thumbtack" @if($nachricht->sticky) style="transform: rotate(45deg)" @endif></i>
                                </a>
                            @endif
                        </div>
                        @include('nachrichten.partials.wp-status')
                    </div>
                </div>
            @endif

            <div class="p-4">
                <div id="carousel_post_{{$nachricht->id}}" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner rounded-lg overflow-hidden">
                        @foreach($nachricht->getMedia('images')->sortBy('name') as $media)
                            <div class="carousel-item @if($loop->first) active @endif">
                                <a href="{{url('/image/'.$media->id)}}" target="_blank" class="block">
                                    <img class="d-block w-full h-auto mx-auto" src="{{url('/image/'.$media->id)}}" alt="{{$media->name}}" style="max-height: 600px; object-fit: contain;">
                                    @if($nachricht->rueckmeldung?->type == 'bild')
                                        <p class="text-center text-sm text-gray-600 mt-2">{{$media->name}}</p>
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>

                    @if(count($nachricht->getMedia('images'))>1)
                        <a class="carousel-control-prev" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="prev">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 shadow-lg" aria-hidden="true">
                                <i class="fas fa-chevron-left text-white"></i>
                            </span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="next">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 shadow-lg" aria-hidden="true">
                                <i class="fas fa-chevron-right text-white"></i>
                            </span>
                            <span class="sr-only">Next</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @elseif($nachricht->no_header)
        <!-- No Header Type -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6 @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach">
            @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author))
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-2 border-b border-gray-200">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex gap-2">
                            @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'abfrage')
                                <a href="{{url('rueckmeldungen/'.$nachricht->rueckmeldung->id."/download")}}"
                                   title="Download"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fa fa-download mr-1"></i>
                                </a>
                            @endif
                            @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)))
                                <a href="{{url('/posts/edit/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                                    <i class="far fa-edit"></i>
                                </a>
                                <a href="{{url('/posts/touch/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht nach oben schieben">
                                    <i class="fas fa-redo"></i>
                                </a>
                            @else
                                <a href="{{url('/posts/touch/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht kopieren">
                                    <i class="far fa-clone"></i>
                                </a>
                            @endif
                            @if($nachricht->released == 0)
                                <a href="{{url('/posts/release/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht veröffentlichen">
                                    <i class="far fa-eye"></i>
                                </a>
                            @endif
                            @if($nachricht->released == 1 and !$nachricht->is_archived)
                                
                                <form action="{{url('/posts/archiv/'.$nachricht->id)}}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                       class="inline-flex items-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                       data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                </form>
                            @endif
                            @if(auth()->user()->can('make sticky'))
                                <a href="{{url('/posts/stick/'.$nachricht->id)}}"
                                   class="inline-flex items-center px-3 py-1.5 @if($nachricht->sticky) bg-green-100 text-green-700 hover:bg-green-200 @else bg-purple-600 hover:bg-purple-700 text-white @endif text-sm font-medium rounded-lg transition-colors duration-200"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht anheften">
                                    <i class="fas fa-thumbtack" @if($nachricht->sticky) style="transform: rotate(45deg)" @endif></i>
                                </a>
                            @endif
                        </div>
                        @include('nachrichten.partials.wp-status')
                    </div>
                </div>
            @endif

            <div class="p-6">
                <div class="prose max-w-none">
                    {!! $nachricht->news !!}
                </div>
            </div>
        </div>
    @else
        <!-- Standard Nachricht Type -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6 transition-shadow duration-300 hover:shadow-xl
            @if($nachricht->released == 0) ring-2  @endif
            @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach"
             id="{{$nachricht->id}}"
             @if($nachricht->is_archived) x-data="{ showArchived: false, showRueckmeldungen: false }" @else x-data="{ showRueckmeldungen: false }" @endif>

            <!-- Header Image -->
            @if(count($nachricht->getMedia('header'))>0)
                <div class="relative h-64 overflow-hidden">
                    <img class="w-full h-full object-cover object-center"
                         src="{{url('/image/'.$nachricht->getMedia('header')->first()->id)}}"
                         alt="{{$nachricht->header}}">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                </div>
            @endif

            <!-- Header Section -->
            <div class="relative @if(count($nachricht->getMedia('header'))>0) @if($nachricht->released == 0) backdrop-blur-sm bg-gradient-to-br from-amber-100 to-amber-200 hover:from-amber-400 hover:to-amber-600  @else backdrop-blur-sm bg-gradient-to-r from-gray-50 to-gray-100 @endif @else @if($nachricht->released == 0) bg-gradient-to-r from-amber-200 to-amber-300 @else bg-gradient-to-r from-gray-50 to-gray-100 @endif @endif px-6 py-3 border-b @if($nachricht->released == 0) border-amber-800 @else border-gray-200 @endif"
                 @if(count($nachricht->getMedia('header'))>0) style="margin-top: -4rem;" @endif>
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                        <!-- Title Section -->
                        <div class="flex-1 min-w-0">
                            <h5 class="text-lg font-bold @if($nachricht->released == 0) text-gray-800 @else text-gray-900 @endif mb-1 flex items-center gap-2 flex-wrap">
                                @if($nachricht->sticky)
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-400 text-yellow-900 rounded-full">
                                        <i class="fas fa-thumbtack text-xs"></i>
                                    </span>
                                @endif
                                <span class="break-words">{{$nachricht->header}}</span>
                                @if($nachricht->released == 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600/90 text-white">
                                        Unveröffentlicht
                                    </span>
                                @endif
                            </h5>

                            @if($nachricht->type != 'image')
                                @include('nachrichten.header.info')
                            @endif
                        </div>

                        <!-- Admin Actions -->
                        @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author))
                            <div class="flex flex-col items-end gap-2">
                                <div class="flex flex-wrap gap-2 md:flex-shrink-0">
                                    @include('nachrichten.header.admin-post')
                                </div>
                                @include('nachrichten.partials.wp-status')
                            </div>
                        @endif
                    </div>

                    <!-- Archive Button -->
                    @if($nachricht->is_archived)
                        <button @click="showArchived = !showArchived"
                                class="mt-3 w-full md:w-auto inline-flex items-center justify-center px-4 py-2 @if($nachricht->released == 0) bg-white/20 hover:bg-white/30 text-white @else bg-blue-500 hover:bg-blue-600 text-white @endif font-medium rounded-lg transition-colors duration-200">
                            <i class="fa mr-2" :class="showArchived ? 'fa-eye-slash' : 'fa-eye'"></i>
                            <span x-text="showArchived ? 'Text ausblenden' : 'Text anzeigen'"></span>
                        </button>
                    @endif
            </div>

            <!-- Content Section -->
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
                 id="Collapse{{$nachricht->id}}">
                <div class="p-6">
                    @if((count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0) and $nachricht->type != 'image')
                        <!-- Content with Media -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2">
                                <div class="prose max-w-none text-gray-700 leading-relaxed">
                                    {!! $nachricht->news !!}
                                </div>
                            </div>
                            <div class="lg:col-span-1 space-y-4">
                                @if(count($nachricht->getMedia('images'))>0)
                                    @include('nachrichten.footer.bilder')
                                @endif

                                @if(count($nachricht->getMedia('files'))>0)
                                    @include('nachrichten.footer.dateiliste')
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- Content without Media -->
                        <div class="prose max-w-none text-gray-700 leading-relaxed">
                            {!! $nachricht->news !!}
                        </div>
                    @endif
                </div>

                <!-- Read Receipt -->
                @if($nachricht->read_receipt == 1)
                    <div class="border-t border-gray-200">
                        @include('nachrichten.footer.read_receipt', ['post' => $nachricht])
                    </div>
                @endif

                <!-- Reactions -->
                <div class="border-t border-gray-200">
                    @include('nachrichten.footer.reactions')
                </div>

                <!-- Anonymous Poll -->
                @include('nachrichten.footer.poll_anonym')

                <!-- Rueckmeldungen Section -->
                @if(!is_null($nachricht->rueckmeldung))
                    <div class="border-t border-gray-200 bg-gray-50 p-6">
                        <div class="space-y-3">
                            @if($nachricht->rueckmeldung->multiple)
                                <div class="flex items-start gap-3 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                    <i class="fas fa-check-double text-blue-600 mt-1"></i>
                                    <p class="text-blue-800 font-medium text-sm">
                                        Es können mehrere Rückmeldungen abgegeben werden.
                                    </p>
                                </div>
                            @endif
                            @if($nachricht->rueckmeldung->pflicht)
                                <div class="flex items-start gap-3 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                                    <i class="fas fa-exclamation-triangle text-red-600 mt-1"></i>
                                    <p class="text-red-800 font-medium text-sm">
                                        Rückmeldung bis {{$nachricht->rueckmeldung->ende->format('d.m.Y')}} ist Pflicht.
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if($nachricht->rueckmeldung != null)
                            @include('nachrichten.elements.progressbar')
                        @endif

                        @if($nachricht->rueckmeldung->type == 'email')
                            @include('nachrichten.footer.rueckmeldung')

                            @can('manage rueckmeldungen')
                                <div class="mt-4">
                                    <a href="{{url('rueckmeldungen')}}"
                                       class="block w-full text-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <i class="fas fa-list mr-2"></i>
                                        Rückmeldungen ansehen
                                    </a>
                                </div>
                            @elsecan('view rueckmeldungen')
                                <button @click="showRueckmeldungen = !showRueckmeldungen"
                                        class="mt-4 w-full inline-flex items-center justify-center px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200">
                                    <i class="fa mr-2" :class="showRueckmeldungen ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    <span x-text="showRueckmeldungen ? '{{$nachricht->userRueckmeldung->count()}} Rückmeldungen ausblenden' : '{{$nachricht->userRueckmeldung->count()}} Rückmeldungen anzeigen'"></span>
                                </button>
                                <div x-show="showRueckmeldungen"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95"
                                     style="display: none;"
                                     id='{{$nachricht->id."_rueckmeldungen"}}'
                                     class="mt-4">
                                    @include('nachrichten.footer.eingegangeneRueckmeldung')
                                </div>
                            @endcan
                        @endif
                    </div>
                @endif

                <!-- Abfrage Type -->
                @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'abfrage')
                    <div class="border-t border-gray-200">
                        @include('nachrichten.footer.abfrage')
                    </div>
                @elseif(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'terminliste')
                    <div class="border-t border-gray-200 p-6">
                        @include('nachrichten.footer.terminliste')
                    </div>
                @elseif(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'bild' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
                    <div class="border-t border-gray-200">
                        @include('nachrichten.footer.imageRueckmeldung')
                    </div>
                @elseif(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'commentable' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
                    <div class="border-t border-gray-200 p-6">
                        @include('nachrichten.footer.comments')
                    </div>
                @endif
            </div>
        </div>
    @endif

