<div class="nachricht {{$nachricht->type}} bg-white rounded-lg shadow-lg overflow-hidden @if($nachricht->released == 0) ring-2 ring-cyan-500 @endif" id="{{$nachricht->id}}">
    <!-- Header Image -->
    @if(count($nachricht->getMedia('header'))>0)
        <img class="w-full h-64 object-cover object-center"
             src="{{url('/image/'.$nachricht->getMedia('header')->first()->id)}}"
             alt="Header-Bild">
    @endif

    <!-- Message Header -->
    <div class="@if($nachricht->released == 0) bg-gradient-to-r from-cyan-500 to-cyan-600 @else bg-gradient-to-r from-blue-600 to-indigo-600 @endif px-6 py-4 border-b @if($nachricht->released == 0) border-cyan-800 @else border-blue-800 @endif">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1">
                <h5 class="text-xl font-bold text-white flex items-center gap-2 mb-2">
                    @if($nachricht->sticky)
                        <i class="fas fa-thumbtack text-yellow-300"></i>
                    @endif
                    {{$nachricht->header}}
                    @if($nachricht->released == 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-cyan-200 text-cyan-800">
                            <i class="fas fa-eye-slash mr-1"></i>
                            unveröffentlicht
                        </span>
                    @endif
                </h5>

                <!-- Meta Information -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-blue-100">
                    <div class="flex items-center gap-1">
                        <i class="fas fa-clock text-xs"></i>
                        <span>aktualisiert: {{$nachricht->updated_at->isoFormat('DD. MMMM YYYY HH:mm')}}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <i class="fas fa-archive text-xs"></i>
                        <span>Archiv ab: {{$nachricht->archiv_ab?->isoFormat('DD. MMMM YYYY')}}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <i class="fas fa-user text-xs"></i>
                        <span>Autor: {{$nachricht->autor?->name}}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author ))
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)))
                        <a href="{{url('/posts/touch/'.$nachricht->id)}}"
                           class="inline-flex items-center justify-center p-2 rounded-lg text-white hover:bg-white hover:bg-opacity-20 transition-all duration-200"
                           title="Nachricht nach oben schieben">
                            <i class="fas fa-redo"></i>
                        </a>
                    @else
                        <a href="{{url('/posts/touch/'.$nachricht->id)}}"
                           class="inline-flex items-center justify-center p-2 rounded-lg text-white hover:bg-white hover:bg-opacity-20 transition-all duration-200"
                           title="Nachricht kopieren">
                            <i class="far fa-clone"></i>
                        </a>
                    @endif
                    @if($nachricht->released == 0)
                        <a href="{{url('/posts/release/'.$nachricht->id)}}"
                           class="inline-flex items-center justify-center p-2 rounded-lg text-white hover:bg-white hover:bg-opacity-20 transition-all duration-200"
                           title="Nachricht veröffentlichen">
                            <i class="far fa-eye"></i>
                        </a>
                    @endif
                </div>
            @endif
        </div>

        <!-- Show/Hide Button for Archived Messages -->
        @if($nachricht->is_archived)
            <div class="mt-4 pt-4 border-t border-blue-700" x-data="{ showContent: false }">
                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 font-medium rounded-lg transition-all duration-200"
                        @click="showContent = !showContent">
                    <i class="fas" :class="showContent ? 'fa-eye-slash' : 'fa-eye'"></i>
                    <span x-text="showContent ? 'Text ausblenden' : 'Text anzeigen'"></span>
                </button>

                <!-- Message Content for Archived Messages -->
                <div class="mt-4" x-show="showContent" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95">
                    <div class="bg-white rounded-lg p-6 shadow-inner">
                        @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Main Content -->
                                <div class="lg:col-span-2">
                                    <div class="prose prose-gray max-w-none">
                                        {!! $nachricht->news !!}
                                    </div>
                                </div>

                                <!-- Sidebar with Images and Files -->
                                <div class="space-y-4">
                                    @if(count($nachricht->getMedia('images'))>0)
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h6 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                                <i class="fas fa-images text-blue-600"></i>
                                                Bilder
                                            </h6>
                                            @include('nachrichten.footer.bilder')
                                        </div>
                                    @endif

                                    @if(count($nachricht->getMedia('files'))>0)
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h6 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                                <i class="fas fa-file-alt text-blue-600"></i>
                                                Dateien
                                            </h6>
                                            @include('nachrichten.footer.dateiliste')
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="prose prose-gray max-w-none">
                                {!! $nachricht->news !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Message Content for Non-Archived Messages -->
    @if(!$nachricht->is_archived)
        <div class="p-6">
            @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
                        <div class="prose prose-gray max-w-none">
                            {!! $nachricht->news !!}
                        </div>
                    </div>

                    <!-- Sidebar with Images and Files -->
                    <div class="space-y-4">
                        @if(count($nachricht->getMedia('images'))>0)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h6 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <i class="fas fa-images text-blue-600"></i>
                                    Bilder
                                </h6>
                                @include('nachrichten.footer.bilder')
                            </div>
                        @endif

                        @if(count($nachricht->getMedia('files'))>0)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h6 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                    Dateien
                                </h6>
                                @include('nachrichten.footer.dateiliste')
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="prose prose-gray max-w-none">
                    {!! $nachricht->news !!}
                </div>
            @endif
        </div>
    @endif

    <!-- Feedback Section -->
    @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'email')
        @if(!$nachricht->is_archived and $nachricht->rueckmeldung->pflicht == 1)
            <!-- Response Status Indicators -->
            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-chart-bar text-blue-600"></i>
                    <span class="text-sm font-medium text-gray-700">Rückmeldungsstatus</span>
                </div>
                <div class="flex items-center gap-1 flex-wrap">
                    @for($x=1; $x <= $nachricht->userRueckmeldung->count(); $x++)
                        <i class="fas fa-user-check text-green-500 text-sm" title="Rückmeldung {{$x}} erhalten"></i>
                    @endfor
                    @for($x=1; $x <= ((round($nachricht->users->where('sorg2', '!=', null)->unique('email')->count()/2)) + $nachricht->users->where('sorg2', 0)->unique('email')->count())-$nachricht->userRueckmeldung->count(); $x++)
                        <i class="fas fa-user-clock text-red-500 text-sm" title="Rückmeldung {{$x}} ausstehend"></i>
                    @endfor
                </div>
            </div>
        @endif

        <!-- Feedback Form/Content -->
        <div class="border-t border-gray-200">
            @include('nachrichten.footer.rueckmeldung')
        </div>

        <!-- Show Feedback Responses (Admin only) -->
        @can('view rueckmeldungen')
            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4" x-data="{ showFeedback: false }">
                <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 font-medium rounded-lg transition-colors duration-200"
                        @click="showFeedback = !showFeedback">
                    <i class="fas" :class="showFeedback ? 'fa-eye-slash' : 'fa-eye'"></i>
                    <span x-text="showFeedback ? 'Rückmeldungen ausblenden' : '{{$nachricht->userRueckmeldung->count()}} Rückmeldungen anzeigen'"></span>
                </button>

                <div class="mt-4" x-show="showFeedback" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95">
                    <div class="bg-white rounded-lg p-4 space-y-3">
                        @foreach($nachricht->userRueckmeldung as $rueckmeldung)
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-circle text-gray-400 text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-gray-900">{{$rueckmeldung->user->name}}</span>
                                        <span class="text-xs text-gray-500">{{$rueckmeldung->created_at->isoFormat('DD.MM.YYYY HH:mm')}}</span>
                                    </div>
                                    <p class="text-sm text-gray-700">{{$rueckmeldung->antwort}}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endcan
    @endif
</div>
